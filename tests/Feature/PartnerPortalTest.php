<?php

namespace Tests\Feature;

use App\Enums\KycStatus;
use App\Enums\PartnerDocumentType;
use App\Enums\PartnerStatus;
use App\Enums\RoleSlug;
use App\Enums\UserType;
use App\Filament\Partner\Pages\BankDetails;
use App\Filament\Partner\Pages\KycDocuments;
use App\Filament\Partner\Pages\SignAgreement;
use App\Filament\Resources\Partners\Pages\ListPartners;
use App\Filament\Resources\Partners\Pages\ViewPartner;
use App\Models\AuditLog;
use App\Models\Partner;
use App\Models\PartnerDocument;
use App\Models\PartnerInvitation;
use App\Models\User;
use App\Notifications\PartnerActivated;
use App\Notifications\PartnerInvited;
use App\Services\Partners\PartnerAgreement;
use App\Services\Partners\PartnerInvitations;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerPortalTest extends TestCase
{
    private function url(string $path = '/'): string
    {
        return 'http://'.config('govoyza.domains.partner').$path;
    }

    private function withMfa(User $user): User
    {
        $user->saveAppAuthenticationSecret(AppAuthentication::make()->generateSecret());

        return $user;
    }

    private function partnerUser(PartnerStatus $status = PartnerStatus::Pending): User
    {
        $partner = new Partner(['legal_name' => 'Secret Visa Supplier Ltd']);
        $partner->status = $status;
        $partner->kyc_status = KycStatus::NotSubmitted;
        $partner->save();

        $user = User::factory()->partner()->create();
        $user->partner_id = $partner->id;
        $user->save();

        return $this->withMfa($user);
    }

    private function staff(RoleSlug $role = RoleSlug::Operations): User
    {
        return $this->withMfa(User::factory()->role($role)->create());
    }

    /** Invites through the service and returns the link token from the email. */
    private function invite(string $email = 'ops@supplier.test'): string
    {
        Notification::fake();
        app(PartnerInvitations::class)->invite($email, 'Supplier Ltd', $this->staff());

        $url = null;
        Notification::assertSentTo(new AnonymousNotifiable, PartnerInvited::class, function ($n, $channels, $notifiable) use (&$url, $email) {
            $url = $n->url;

            return $notifiable->routes['mail'] === $email;
        });

        return basename(parse_url($url, PHP_URL_PATH));
    }

    private function completeOnboarding(Partner $partner): void
    {
        foreach (PartnerDocumentType::required() as $type) {
            $doc = new PartnerDocument;
            $doc->forceFill([
                'partner_id' => $partner->id, 'document_type' => $type, 'disk' => 'private', 'path' => "partners/{$partner->id}/x.pdf",
                'original_name' => 'x.pdf', 'mime_type' => 'application/pdf', 'size' => 1, 'sha256' => str_repeat('a', 64),
            ])->save();
        }
        $partner->bank_details = ['account_name' => 'Supplier Ltd', 'bank_name' => 'Bank', 'branch' => null, 'account_number' => '1234567890', 'routing_number' => null];
        $partner->agreement_version = app(PartnerAgreement::class)->version();
        $partner->agreement_signed_at = now();
        $partner->agreement_signed_name = 'Owner Name';
        $partner->save();
    }

    // Invitations ------------------------------------------------------------

    public function test_operations_can_invite_and_finance_cannot(): void
    {
        Notification::fake();
        Filament::setCurrentPanel('admin');

        $this->actingAs($this->staff(RoleSlug::Operations));
        Livewire::test(ListPartners::class)
            ->callAction('invite', data: ['email' => 'New@Supplier.test', 'legal_name' => 'New Supplier'])
            ->assertHasNoActionErrors();

        $invitation = PartnerInvitation::sole();
        $this->assertSame('new@supplier.test', $invitation->email);
        $this->assertSame(64, strlen($invitation->token_hash));
        $this->assertTrue(AuditLog::where('action', 'partner.invited')->exists());

        $this->actingAs($this->staff(RoleSlug::Finance))
            ->get('http://'.config('govoyza.domains.admin').'/partners')->assertForbidden();
    }

    public function test_invite_link_creates_a_pending_partner_and_its_first_user(): void
    {
        $token = $this->invite();

        $this->get($this->url("/invite/{$token}"))->assertOk()->assertSee('ops@supplier.test');

        $this->post($this->url("/invite/{$token}"), [
            'legal_name' => 'Supplier Ltd', 'name' => 'Rahim Khan', 'phone' => '+8801711000000',
            'password' => 'Secret123pass', 'password_confirmation' => 'Secret123pass',
        ])->assertRedirect(Filament::getPanel('partner')->getUrl());

        $user = User::firstWhere('email', 'ops@supplier.test');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserType::PartnerUser, $user->type);
        $this->assertTrue($user->hasRole(RoleSlug::Partner));
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame(PartnerStatus::Pending, $user->partner->status);
        $this->assertSame('Supplier Ltd', $user->partner->legal_name);
        $this->assertNotNull(PartnerInvitation::sole()->accepted_at);

        // The link works once.
        auth()->logout();
        $this->get($this->url("/invite/{$token}"))->assertStatus(410);
    }

    public function test_expired_unknown_and_taken_invites_are_refused(): void
    {
        $token = $this->invite();

        $this->get($this->url('/invite/not-a-real-token'))->assertStatus(410);

        $this->travel(config('govoyza.partner.invitation_days') + 1)->days();
        $this->get($this->url("/invite/{$token}"))->assertStatus(410);
        $this->travelBack();

        User::factory()->create(['email' => 'taken@supplier.test']);
        $taken = $this->invite('taken@supplier.test');
        $this->post($this->url("/invite/{$taken}"), [
            'legal_name' => 'X', 'name' => 'Y', 'password' => 'Secret123pass', 'password_confirmation' => 'Secret123pass',
        ])->assertSessionHasErrors('legal_name');
        $this->assertSame(0, Partner::count());
    }

    // Panel access -------------------------------------------------------------

    public function test_only_partner_users_get_into_the_partner_portal(): void
    {
        $this->actingAs($this->partnerUser())->get($this->url())->assertOk()->assertSee('To finish your application');

        foreach ([User::factory()->create(), $this->staff(RoleSlug::Owner)] as $other) {
            $this->actingAs($other)->get($this->url())->assertForbidden();
        }

        // Partners never get into the admin console.
        $this->actingAs($this->partnerUser(PartnerStatus::Active))
            ->get('http://'.config('govoyza.domains.admin').'/')->assertForbidden();
    }

    public function test_2fa_is_required_and_suspended_partners_are_locked_out(): void
    {
        $user = $this->partnerUser();
        $user->saveAppAuthenticationSecret(null);
        $this->actingAs($user)->get($this->url())
            ->assertRedirect(route('filament.partner.auth.multi-factor-authentication.set-up-required'));

        $suspended = $this->partnerUser(PartnerStatus::Suspended);
        $this->actingAs($suspended)->get($this->url())->assertForbidden();
    }

    // Onboarding ---------------------------------------------------------------

    public function test_kyc_upload_is_private_and_completes_kyc_when_both_required_documents_are_in(): void
    {
        Storage::fake('private');
        $user = $this->partnerUser();
        $this->actingAs($user);
        Filament::setCurrentPanel('partner');

        foreach (['trade_licence' => 'licence.pdf', 'owner_id' => 'nid.jpg'] as $type => $name) {
            $file = str_ends_with($name, '.pdf')
                ? UploadedFile::fake()->create($name, 200, 'application/pdf')
                : UploadedFile::fake()->image($name);

            Livewire::test(KycDocuments::class)
                ->fillForm(['document_type' => $type, 'file' => $file])
                ->call('save')
                ->assertHasNoFormErrors();
        }

        $documents = $user->partner->documents;
        $this->assertCount(2, $documents);
        foreach ($documents as $document) {
            Storage::disk('private')->assertExists($document->path);
            $this->assertStringStartsWith("partners/{$user->partner_id}/", $document->path);
            $this->assertStringNotContainsString('licence', $document->path);
            $this->assertSame(64, strlen($document->sha256));
        }
        $this->assertSame(KycStatus::Submitted, $user->partner->fresh()->kyc_status);
        $this->assertTrue(AuditLog::where('action', 'partner.created')->where('auditable_type', (new PartnerDocument)->getMorphClass())->exists());
    }

    public function test_bank_details_are_validated_and_stored_encrypted(): void
    {
        $user = $this->partnerUser();
        $this->actingAs($user);
        Filament::setCurrentPanel('partner');

        Livewire::test(BankDetails::class)
            ->fillForm(['account_name' => 'Supplier Ltd', 'bank_name' => 'City Bank', 'account_number' => '12ab'])
            ->call('save')
            ->assertHasFormErrors(['account_number']);

        Livewire::test(BankDetails::class)
            ->fillForm(['account_name' => 'Supplier Ltd', 'bank_name' => 'City Bank', 'branch' => 'Gulshan', 'account_number' => '1234567890123'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('1234567890123', $user->partner->fresh()->bank_details['account_number']);
        $raw = DB::table('partners')->where('id', $user->partner_id)->value('bank_details');
        $this->assertStringNotContainsString('1234567890123', $raw);

        // The audit trail masks it too (bank_details is a hidden attribute).
        $log = AuditLog::where('action', 'partner.updated')->latest('id')->first();
        $this->assertSame('***', $log->new_values['bank_details']);
    }

    public function test_agreement_signature_records_who_when_where_and_what(): void
    {
        $user = $this->partnerUser();
        $this->actingAs($user);
        Filament::setCurrentPanel('partner');

        Livewire::test(SignAgreement::class)
            ->fillForm(['full_name' => 'Rahim Khan', 'accept' => false])
            ->call('save')
            ->assertHasFormErrors(['accept']);

        Livewire::test(SignAgreement::class)
            ->fillForm(['full_name' => 'Rahim Khan', 'accept' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $partner = $user->partner->fresh();
        $agreement = app(PartnerAgreement::class);
        $this->assertTrue($partner->hasSignedCurrentAgreement());
        $this->assertSame('Rahim Khan', $partner->agreement_signed_name);
        $this->assertSame($agreement->version(), $partner->agreement_version);
        $this->assertSame($agreement->sha256(), $partner->agreement_text_sha256);
        $this->assertSame('127.0.0.1', $partner->agreement_ip);
        $this->assertSame($user->id, $partner->agreement_signed_by);

        // A new agreement version asks for a new signature.
        config(['govoyza.partner.agreement_version' => 'next']);
        $this->assertFalse($partner->hasSignedCurrentAgreement());
    }

    // Admin approval -----------------------------------------------------------

    public function test_approval_needs_complete_onboarding_then_activates_and_emails(): void
    {
        Notification::fake();
        $user = $this->partnerUser();
        $partner = $user->partner;
        $this->actingAs($this->staff());
        Filament::setCurrentPanel('admin');

        Livewire::test(ViewPartner::class, ['record' => $partner->getRouteKey()])->callAction('approve');
        $this->assertSame(PartnerStatus::Pending, $partner->fresh()->status);

        $this->completeOnboarding($partner);
        Livewire::test(ViewPartner::class, ['record' => $partner->getRouteKey()])->callAction('approve');

        $partner->refresh();
        $this->assertSame(PartnerStatus::Active, $partner->status);
        $this->assertSame(KycStatus::Approved, $partner->kyc_status);
        $this->assertNotNull($partner->approved_by);
        Notification::assertSentTo($user, PartnerActivated::class);
        $this->assertTrue(AuditLog::where('action', 'partner.approved')->exists());
    }

    public function test_suspend_locks_the_partner_out_and_reactivate_restores_it(): void
    {
        $user = $this->partnerUser(PartnerStatus::Active);
        $partner = $user->partner;
        $partner->approved_at = now();
        $partner->save();
        $this->actingAs($this->staff());
        Filament::setCurrentPanel('admin');

        Livewire::test(ViewPartner::class, ['record' => $partner->getRouteKey()])
            ->callAction('suspend', data: ['reason' => 'Late on three jobs']);
        $this->assertSame(PartnerStatus::Suspended, $partner->fresh()->status);
        $this->assertFalse($user->fresh()->canAccessPanel(Filament::getPanel('partner')));

        Livewire::test(ViewPartner::class, ['record' => $partner->getRouteKey()])->callAction('reactivate');
        $this->assertSame(PartnerStatus::Active, $partner->fresh()->status);
    }

    public function test_kyc_documents_are_downloadable_by_staff_but_not_by_other_partners(): void
    {
        Storage::fake('private');
        $owner = $this->partnerUser();
        Storage::disk('private')->put("partners/{$owner->partner_id}/abc.pdf", 'pdf');
        $doc = new PartnerDocument;
        $doc->forceFill([
            'partner_id' => $owner->partner_id, 'document_type' => PartnerDocumentType::TradeLicence, 'disk' => 'private',
            'path' => "partners/{$owner->partner_id}/abc.pdf", 'original_name' => 'licence.pdf',
            'mime_type' => 'application/pdf', 'size' => 3, 'sha256' => str_repeat('b', 64),
        ])->save();

        $this->assertTrue($owner->can('view', $doc));
        $this->assertFalse($this->partnerUser()->can('view', $doc));
        $this->assertFalse(User::factory()->create()->can('view', $doc));

        $this->actingAs($this->staff());
        Filament::setCurrentPanel('admin');
        Livewire::test(ViewPartner::class, ['record' => $owner->partner->getRouteKey()])
            ->callAction('download', data: ['document_id' => $doc->id])
            ->assertFileDownloaded('licence.pdf');
        $this->assertTrue(AuditLog::where('action', 'admin.partner_document_downloaded')->exists());
    }

    public function test_public_site_never_mentions_the_partner_portal_or_partners(): void
    {
        $partner = $this->partnerUser()->partner;

        foreach (['/', '/login', '/register'] as $path) {
            $this->get($path)
                ->assertDontSee(config('govoyza.domains.partner'))
                ->assertDontSee($partner->legal_name);
        }
    }
}
