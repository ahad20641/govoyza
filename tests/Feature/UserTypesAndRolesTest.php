<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\UserType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use LogicException;
use Tests\TestCase;

class UserTypesAndRolesTest extends TestCase
{
    public function test_every_role_maps_to_one_user_type(): void
    {
        $this->assertSame(UserType::Admin, RoleSlug::Owner->userType());
        $this->assertSame(UserType::Admin, RoleSlug::Operations->userType());
        $this->assertSame(UserType::Admin, RoleSlug::Finance->userType());
        $this->assertSame(UserType::Customer, RoleSlug::Client->userType());
        $this->assertSame(UserType::AgentUser, RoleSlug::Agency->userType());
        $this->assertSame(UserType::PartnerUser, RoleSlug::Partner->userType());
    }

    public function test_type_is_derived_from_role_when_missing(): void
    {
        $user = new User(['name' => 'A', 'email' => 'a@example.com', 'password' => 'x']);
        $user->role_id = Role::forSlug(RoleSlug::Finance)->id;
        $user->save();

        $this->assertSame(UserType::Admin, $user->fresh()->type);
    }

    public function test_type_and_role_must_agree(): void
    {
        $customer = User::factory()->create();
        $customer->role_id = Role::forSlug(RoleSlug::Owner)->id;

        $this->expectException(LogicException::class);
        $customer->save();
    }

    public function test_assign_role_moves_type_and_permissions_together(): void
    {
        $user = User::factory()->operations()->create();
        $this->assertTrue(Gate::forUser($user)->allows('applications.verify_documents'));

        $user->assignRole(RoleSlug::Finance)->save();

        $this->assertSame(UserType::Admin, $user->type);
        $this->assertTrue($user->hasRole(RoleSlug::Finance));
        $this->assertFalse(Gate::forUser($user)->allows('applications.verify_documents'));
        $this->assertTrue(Gate::forUser($user)->allows('payments.manage'));
    }

    public function test_staff_permissions_are_least_privilege(): void
    {
        $operations = User::factory()->operations()->create();
        $finance = User::factory()->finance()->create();

        foreach ([$operations, $finance] as $staff) {
            $this->assertTrue(Gate::forUser($staff)->allows('admin.access'));
            $this->assertTrue(Gate::forUser($staff)->allows('applications.view_any'));
            foreach (['pricing.manage', 'staff.manage', 'settings.manage', 'audit.view'] as $ownerOnly) {
                $this->assertFalse(Gate::forUser($staff)->allows($ownerOnly), $ownerOnly);
            }
        }

        // Finance cannot edit orders.
        $this->assertFalse(Gate::forUser($finance)->allows('applications.update_status'));
        $this->assertFalse(Gate::forUser($finance)->allows('applications.verify_documents'));
        $this->assertTrue(Gate::forUser($finance)->allows('applications.refund'));
        $this->assertTrue(Gate::forUser($finance)->allows('payments.manage'));
        $this->assertTrue(Gate::forUser($finance)->allows('ledger.manage'));
        $this->assertTrue(Gate::forUser($finance)->allows('exchange_rates.manage'));

        // Operations runs orders but never touches money.
        $this->assertTrue(Gate::forUser($operations)->allows('applications.update_status'));
        $this->assertTrue(Gate::forUser($operations)->allows('applications.verify_documents'));
        $this->assertTrue(Gate::forUser($operations)->allows('partners.manage'));
        foreach (['applications.refund', 'payments.manage', 'ledger.manage', 'exchange_rates.manage', 'reports.view_all'] as $money) {
            $this->assertFalse(Gate::forUser($operations)->allows($money), $money);
        }
    }

    public function test_owner_bypass_needs_admin_type(): void
    {
        $owner = User::factory()->owner()->create();
        $this->assertTrue($owner->isOwner());

        // A row with a mismatched type (written around the model) gets no bypass.
        User::query()->whereKey($owner->id)->update(['type' => UserType::Customer->value]);
        $this->assertFalse($owner->fresh()->isOwner());
        $this->assertFalse(Gate::forUser($owner->fresh())->allows('settings.manage'));
    }

    public function test_create_admin_command_creates_staff_roles_only(): void
    {
        $this->artisan('govoyza:create-admin', [
            '--name' => 'Fin', '--email' => 'fin@govoyza.test', '--role' => 'finance',
        ])->expectsQuestion('Password (hidden)', 'Str0ng!Passw0rd')
            ->expectsQuestion('Password again', 'Str0ng!Passw0rd')
            ->assertSuccessful();

        $finance = User::firstWhere('email', 'fin@govoyza.test');
        $this->assertSame(UserType::Admin, $finance->type);
        $this->assertTrue($finance->hasRole(RoleSlug::Finance));

        $this->artisan('govoyza:create-admin', [
            '--name' => 'X', '--email' => 'x@govoyza.test', '--role' => 'client',
        ])->assertFailed(); // rejected before any password is asked for
        $this->assertNull(User::firstWhere('email', 'x@govoyza.test'));
    }
}
