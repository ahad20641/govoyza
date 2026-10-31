<?php

namespace Tests\Feature\Auth;

use App\Enums\AgencyStatus;
use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgencyRegistrationTest extends TestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'agency_name' => 'Sky Route Travels',
            'trade_license_number' => 'TRAD/DSCC/123456',
            'business_email' => 'office@skyroute.test',
            'business_phone' => '+8801711000000',
            'address' => 'House 1, Road 2, Dhaka',
            'name' => 'Karim Ahmed',
            'email' => 'karim@skyroute.test',
            'phone' => '+8801811000000',
            'password' => 'Secret123pass',
            'password_confirmation' => 'Secret123pass',
            'terms' => '1',
            'documents' => [
                'trade_license' => UploadedFile::fake()->create('license.pdf', 300, 'application/pdf'),
                'owner_id' => UploadedFile::fake()->image('nid.jpg'),
            ],
        ], $overrides);
    }

    public function test_agency_registers_as_pending_with_private_documents(): void
    {
        Storage::fake('private');

        $this->post('/register/agency', $this->payload())->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'karim@skyroute.test')->firstOrFail();
        $this->assertTrue($user->hasRole(RoleSlug::Agency));
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertSame(AgencyStatus::Pending, $user->agency->status);
        $this->assertCount(2, $user->agency->documents);

        foreach ($user->agency->documents as $doc) {
            Storage::disk('private')->assertExists($doc->path);
            // Stored name must be random, not the client-supplied filename.
            $this->assertStringNotContainsString('license', $doc->path);
            $this->assertStringNotContainsString('nid', $doc->path);
            $this->assertSame(64, strlen($doc->sha256));
        }
    }

    public function test_required_documents_and_file_types_are_enforced(): void
    {
        Storage::fake('private');

        $this->post('/register/agency', $this->payload(['documents' => [
            'trade_license' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php'),
        ]]))->assertSessionHasErrors(['documents.trade_license', 'documents.owner_id']);

        $this->post('/register/agency', $this->payload(['documents' => [
            'trade_license' => UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf'),
            'owner_id' => UploadedFile::fake()->image('nid.jpg'),
        ]]))->assertSessionHasErrors('documents.trade_license');

        $this->assertDatabaseCount('agencies', 0);
    }

    public function test_pending_agency_is_confined_to_pending_page(): void
    {
        $user = User::factory()->agency()->status(UserStatus::Pending)->create();

        $this->actingAs($user)->get('/agency')->assertRedirect(route('account.pending'));
        $this->actingAs($user)->get('/account/pending')->assertOk()->assertSee('Pending approval');
    }
}
