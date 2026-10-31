<?php

namespace App\Console\Commands;

use App\Services\Access\PermissionSynchronizer;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    protected $signature = 'govoyza:sync-permissions';

    protected $description = 'Sync system roles and the permission catalogue (config/permissions.php) into the database';

    public function handle(PermissionSynchronizer $sync): int
    {
        $sync->sync();
        $this->info('Roles and permissions synced.');

        return self::SUCCESS;
    }
}
