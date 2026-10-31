<?php

namespace Database\Seeders;

use App\Services\Access\PermissionSynchronizer;
use Illuminate\Database\Seeder;

/**
 * Seeds only system data (roles, permissions, default settings).
 * No users, agencies, vendors or transactions are created here.
 * Create the first admin with: php artisan govoyza:create-admin
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionSynchronizer::class)->sync();
        $this->call(SettingsSeeder::class);
    }
}
