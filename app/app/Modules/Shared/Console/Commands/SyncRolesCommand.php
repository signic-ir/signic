<?php

declare(strict_types=1);

namespace App\Modules\Shared\Console\Commands;

use App\Modules\Shared\Models\Role;
use App\Modules\Shared\Services\RoleService;
use Illuminate\Console\Command;

class SyncRolesCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'shared:sync-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply the default role permissions and grant every permission to the super admin';

    public function handle(RoleService $roles): int
    {
        foreach (Role::query()->orderBy('id')->get() as $role) {
            $roles->applyDefaultPermissions($role);
        }

        $superAdmin = $roles->syncSuperAdmin();

        $this->info('The permissions of the roles have been synchronised.');
        $this->line(sprintf(
            'The super admin "%s" holds %d permission(s).',
            $superAdmin->name,
            $superAdmin->permissions()->count()
        ));

        return self::SUCCESS;
    }
}
