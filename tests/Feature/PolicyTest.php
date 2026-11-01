<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AgencyDocument;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Country;
use App\Models\ExchangeRate;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    private function agencyFor(User $owner): Agency
    {
        $agency = new Agency([
            'name' => 'Sky Route', 'slug' => 'sky-route-'.$owner->id, 'trade_license_number' => 'T-1',
            'business_email' => 'office@example.com', 'business_phone' => '+8801711000000', 'address' => 'Dhaka',
        ]);
        $agency->owner()->associate($owner);
        $agency->save();

        return $agency;
    }

    private function documentFor(Agency $agency): AgencyDocument
    {
        return $agency->documents()->create([
            'document_type' => 'trade_license', 'disk' => 'private', 'path' => 'agencies/x.pdf',
            'original_name' => 'x.pdf', 'mime_type' => 'application/pdf', 'size' => 1, 'sha256' => str_repeat('a', 64),
        ]);
    }

    public function test_clients_see_only_their_own_profile(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $mine = Client::forceCreate(['user_id' => $me->id]);
        $theirs = Client::forceCreate(['user_id' => $other->id]);

        $this->assertTrue(Gate::forUser($me)->allows('view', $mine));
        $this->assertTrue(Gate::forUser($me)->allows('update', $mine));
        $this->assertFalse(Gate::forUser($me)->allows('view', $theirs));
        $this->assertFalse(Gate::forUser($me)->allows('viewAny', Client::class));

        $this->assertTrue(Gate::forUser(User::factory()->operations()->create())->allows('view', $theirs));
        $this->assertFalse(Gate::forUser(User::factory()->finance()->create())->allows('update', $theirs));
    }

    public function test_agencies_see_only_their_own_agency_and_documents(): void
    {
        $ownerA = User::factory()->agency()->create();
        $ownerB = User::factory()->agency()->create();
        $agencyA = $this->agencyFor($ownerA);
        $docB = $this->documentFor($this->agencyFor($ownerB));

        $this->assertTrue(Gate::forUser($ownerA)->allows('view', $agencyA));
        $this->assertFalse(Gate::forUser($ownerA)->allows('approve', $agencyA));
        $this->assertFalse(Gate::forUser($ownerA)->allows('view', $docB));
        $this->assertTrue(Gate::forUser($ownerB)->allows('view', $docB));

        $operations = User::factory()->operations()->create();
        $this->assertTrue(Gate::forUser($operations)->allows('approve', $agencyA));
        $this->assertTrue(Gate::forUser($operations)->allows('review', $docB));
        $this->assertFalse(Gate::forUser(User::factory()->finance()->create())->allows('approve', $agencyA));
    }

    public function test_vendors_are_hidden_from_everyone_but_operations_and_themselves(): void
    {
        $vendorUser = User::factory()->partner()->create();
        $vendor = Vendor::forceCreate(['user_id' => $vendorUser->id, 'name' => 'Secret Supplier', 'code' => 'V1']);

        $this->assertTrue(Gate::forUser($vendorUser)->allows('view', $vendor));
        $this->assertFalse(Gate::forUser($vendorUser)->allows('update', $vendor));
        $this->assertTrue(Gate::forUser(User::factory()->operations()->create())->allows('update', $vendor));

        foreach ([User::factory()->create(), User::factory()->agency()->create(), User::factory()->finance()->create()] as $user) {
            $this->assertFalse(Gate::forUser($user)->allows('view', $vendor));
            $this->assertFalse(Gate::forUser($user)->allows('viewAny', Vendor::class));
        }
    }

    public function test_exchange_rates_are_finance_only_and_append_only(): void
    {
        $finance = User::factory()->finance()->create();
        $rate = ExchangeRate::forceCreate(['base' => 'USD', 'quote' => 'BDT', 'rate' => '120.5', 'buffer_pct' => '2', 'effective_at' => now()]);

        $this->assertTrue(Gate::forUser($finance)->allows('create', ExchangeRate::class));
        $this->assertFalse(Gate::forUser($finance)->allows('update', $rate));
        $this->assertFalse(Gate::forUser($finance)->allows('delete', $rate));
        $this->assertFalse(Gate::forUser(User::factory()->operations()->create())->allows('create', ExchangeRate::class));
    }

    public function test_owner_only_areas(): void
    {
        $log = AuditLog::create(['action' => 'test']);
        $setting = Setting::create(['group' => 'g', 'key' => 'k', 'value' => 'v', 'type' => 'string']);
        $role = Role::first();

        foreach ([User::factory()->operations()->create(), User::factory()->finance()->create()] as $staff) {
            $this->assertFalse(Gate::forUser($staff)->allows('view', $log));
            $this->assertFalse(Gate::forUser($staff)->allows('update', $setting));
            $this->assertFalse(Gate::forUser($staff)->allows('viewAny', Role::class));
        }

        $owner = User::factory()->owner()->create();
        $this->assertTrue(Gate::forUser($owner)->allows('view', $log));
        $this->assertTrue(Gate::forUser($owner)->allows('update', $setting));
        $this->assertTrue(Gate::forUser($owner)->allows('view', $role));
    }

    public function test_user_management_follows_the_target_type(): void
    {
        $operations = User::factory()->operations()->create();
        $customer = User::factory()->create();
        $finance = User::factory()->finance()->create();

        $this->assertTrue(Gate::forUser($operations)->allows('update', $customer));
        $this->assertTrue(Gate::forUser($operations)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($operations)->allows('update', $finance));
        $this->assertFalse(Gate::forUser($operations)->allows('changeRole', $operations));
        $this->assertFalse(Gate::forUser($operations)->allows('create', User::class));

        $this->assertTrue(Gate::forUser($customer)->allows('update', $customer));
        $this->assertFalse(Gate::forUser($customer)->allows('changeRole', $customer));
        $this->assertFalse(Gate::forUser($customer)->allows('view', $finance));
        $this->assertFalse(Gate::forUser($customer)->allows('viewAny', User::class));
    }

    public function test_reference_data_is_public_to_read_and_catalogue_staff_to_change(): void
    {
        $this->assertTrue(Gate::forUser(null)->allows('viewAny', Country::class));
        $this->assertTrue(Gate::forUser(User::factory()->create())->allows('viewAny', Country::class));
        $this->assertFalse(Gate::forUser(User::factory()->create())->allows('create', Country::class));
        $this->assertFalse(Gate::forUser(User::factory()->finance()->create())->allows('create', Country::class));
        $this->assertTrue(Gate::forUser(User::factory()->operations()->create())->allows('create', Country::class));
    }
}
