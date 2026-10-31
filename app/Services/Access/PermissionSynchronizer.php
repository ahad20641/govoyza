<?php

namespace App\Services\Access;

use App\Enums\RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

/** Idempotently syncs system roles + config/permissions.php into the database. */
class PermissionSynchronizer
{
    public function sync(): void
    {
        DB::transaction(function () {
            $roles = [];
            foreach (RoleSlug::cases() as $slug) {
                $roles[$slug->value] = Role::updateOrCreate(
                    ['slug' => $slug->value],
                    ['name' => $slug->label(), 'is_system' => true],
                );
            }

            $byRole = array_fill_keys(array_keys($roles), []);

            foreach (config('permissions') as $group => $permissions) {
                foreach ($permissions as $slug => [$label, $roleSlugs]) {
                    $permission = Permission::updateOrCreate(['slug' => $slug], ['name' => $label, 'group' => $group]);
                    foreach ($roleSlugs as $roleSlug) {
                        $byRole[$roleSlug][] = $permission->id;
                    }
                }
            }

            foreach ($roles as $slug => $role) {
                $role->permissions()->sync($byRole[$slug]);
            }

            Permission::whereNotIn('slug', collect(config('permissions'))->flatMap(fn ($p) => array_keys($p)))->delete();
        });
    }
}
