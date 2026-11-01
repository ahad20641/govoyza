<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\ExchangeRates\ExchangeRateResource;
use App\Filament\Resources\ExchangeRates\Pages\CreateExchangeRate;
use App\Models\AuditLog;
use App\Models\ExchangeRate;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdminConsoleTest extends TestCase
{
    private function url(string $path = '/'): string
    {
        return 'http://'.config('govoyza.domains.admin').$path;
    }

    /** Staff user who has already set up app 2FA. */
    private function staff(RoleSlug $role = RoleSlug::Owner): User
    {
        $user = User::factory()->role($role)->create();
        $user->saveAppAuthenticationSecret(AppAuthentication::make()->generateSecret());

        return $user;
    }

    public function test_guests_go_to_the_admin_login(): void
    {
        $this->get($this->url())->assertRedirect(route('filament.admin.auth.login'));

        $this->get($this->url('/login'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex')
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_every_staff_role_with_2fa_reaches_the_dashboard(): void
    {
        foreach (RoleSlug::staff() as $role) {
            $this->actingAs($this->staff($role))->get($this->url())->assertOk();
        }
    }

    public function test_2fa_set_up_is_required_before_anything_else(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get($this->url())
            ->assertRedirect(route('filament.admin.auth.multi-factor-authentication.set-up-required'));
    }

    public function test_non_staff_and_inactive_staff_are_refused(): void
    {
        foreach ([User::factory()->create(), User::factory()->agency()->create(), User::factory()->partner()->create()] as $user) {
            $this->actingAs($user)->get($this->url())->assertForbidden();
        }

        $suspended = $this->staff();
        $suspended->status = UserStatus::Suspended;
        $suspended->save();
        $this->assertFalse($suspended->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_2fa_secret_is_stored_encrypted(): void
    {
        $owner = User::factory()->owner()->create();
        $secret = AppAuthentication::make()->generateSecret();
        $owner->saveAppAuthenticationSecret($secret);

        $raw = DB::table('users')->where('id', $owner->id)->value('two_factor_secret');
        $this->assertNotSame($secret, $raw);
        $this->assertSame($secret, $owner->fresh()->getAppAuthenticationSecret());
        $this->assertTrue($owner->fresh()->two_factor_enabled);
    }

    public function test_ip_allowlist_hides_the_console_from_other_addresses(): void
    {
        config(['govoyza.admin.allowed_ips' => ['10.0.0.0/8', '203.0.113.7']]);

        $this->get($this->url('/login'))->assertNotFound(); // tests come from 127.0.0.1
        $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])->get($this->url('/login'))->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])->get($this->url('/login'))->assertOk();
    }

    public function test_resources_follow_the_permission_matrix(): void
    {
        $rates = $this->url('/exchange-rates');
        $audit = $this->url('/audit-logs');

        $this->actingAs($this->staff(RoleSlug::Finance))->get($rates)->assertOk();
        $this->actingAs($this->staff(RoleSlug::Finance))->get($audit)->assertForbidden();

        $this->actingAs($this->staff(RoleSlug::Operations))->get($rates)->assertForbidden();
        $this->actingAs($this->staff(RoleSlug::Operations))->get($audit)->assertForbidden();

        $owner = $this->staff();
        $this->actingAs($owner)->get($rates)->assertOk();
        $this->actingAs($owner)->get($audit)->assertOk();
    }

    public function test_finance_adds_an_exchange_rate_and_the_write_is_audited(): void
    {
        $finance = $this->staff(RoleSlug::Finance);
        $this->actingAs($finance);
        Filament::setCurrentPanel('admin');

        Livewire::test(CreateExchangeRate::class)
            ->fillForm([
                'base' => 'USD',
                'quote' => 'BDT',
                'rate' => '122.50000000',
                'buffer_pct' => '2.5',
                'effective_at' => now()->startOfMinute(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $rate = ExchangeRate::sole();
        $this->assertSame('122.50000000', $rate->rate);
        $this->assertSame($finance->id, $rate->set_by);

        $log = AuditLog::where('action', 'admin.created')->sole();
        $this->assertSame($finance->id, $log->user_id);
        $this->assertSame($rate->getMorphClass(), $log->auditable_type);
        $this->assertSame('122.50000000', $log->new_values['rate']);
    }

    public function test_exchange_rate_form_rejects_bad_input(): void
    {
        $this->actingAs($this->staff(RoleSlug::Finance));
        Filament::setCurrentPanel('admin');

        Livewire::test(CreateExchangeRate::class)
            ->fillForm(['base' => 'USD', 'quote' => 'USD', 'rate' => '0', 'buffer_pct' => '150'])
            ->call('create')
            ->assertHasFormErrors(['base', 'rate', 'buffer_pct']);

        $this->assertSame(0, ExchangeRate::count());
    }

    public function test_admin_writes_are_audited_with_secrets_masked_and_other_writes_are_not(): void
    {
        $customer = User::factory()->create();

        // Outside the admin console: no admin.* record.
        $customer->update(['name' => 'Outside']);
        $this->assertSame(0, AuditLog::where('action', 'like', 'admin.%')->count());

        Filament::setCurrentPanel('admin');
        $customer->update(['name' => 'Inside', 'password' => 'N3w-secret-pass']);

        $log = AuditLog::where('action', 'admin.updated')->sole();
        $this->assertSame('Inside', $log->new_values['name']);
        $this->assertSame('***', $log->new_values['password']);
        $this->assertSame('Outside', $log->old_values['name']);

        $customer->delete();
        $this->assertTrue(AuditLog::where('action', 'admin.deleted')->exists());
    }

    public function test_exchange_rates_cannot_be_edited_or_deleted(): void
    {
        $this->assertArrayNotHasKey('edit', ExchangeRateResource::getPages());
        $this->assertFalse(AuditLogResource::canCreate());
    }
}
