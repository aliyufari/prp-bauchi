<?php

namespace Database\Seeders;

use App\Enums\Role as RoleName;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Lets field roles manage the people below them inside their own location.
 *
 * Permissions only open the door. Everything else is already enforced by the app:
 *  - Jurisdiction::coversUser / visibleTo -> a manager only sees and touches people in their
 *    own state / zone / LGA / ward / polling unit;
 *  - RoleHierarchy::canManage / canAssign -> nobody manages anyone at or above their own rank;
 *  - the location pickers only offer levels strictly below the manager's own.
 *
 * Additive and idempotent: it never removes a permission, so it is safe to run repeatedly
 *   php artisan db:seed --class=ManagerPermissionsSeeder
 */
class ManagerPermissionsSeeder extends Seeder
{
    private const FULL = ['view', 'create', 'update', 'delete', 'export'];
    private const EDIT = ['view', 'create', 'update', 'export'];
    private const READ = ['view', 'export'];

    /** role => module => actions */
    private function matrix(): array
    {
        return [
            // Coordinators run their area: everything below them, at their level or lower.
            RoleName::COORDINATOR->value => [
                'dashboard' => ['view', 'export'], // opens the management layout and the scoped dashboard
                'members' => self::FULL,
                'party-agents' => self::FULL,
                'stakeholders' => self::FULL,
                'supervisory-agents' => self::FULL,
                'returning-officers' => self::FULL,
                'coordinators' => self::FULL, // lower-level coordinators only (hierarchy rule)
                'polling-units' => ['view', 'manage'],
                'results' => ['view', 'upload', 'verify'],
                'campaign-teams' => ['view'],
            ],
            // Supervisory agents supervise agents and members on the ground.
            RoleName::SUPERVISORY_AGENT->value => [
                'dashboard' => ['view', 'export'], // opens the management layout and the scoped dashboard
                'members' => self::EDIT,
                'party-agents' => self::FULL,
                'returning-officers' => self::READ,
                'polling-units' => ['view'],
                'results' => ['view', 'upload', 'verify'],
            ],
            // Returning officers collate results for their LGA and manage the agents in it.
            RoleName::RETURNING_OFFICER->value => [
                'dashboard' => ['view', 'export'], // opens the management layout and the scoped dashboard
                'members' => self::READ,
                'party-agents' => self::EDIT,
                'polling-units' => ['view'],
                'results' => ['view', 'upload', 'verify'],
            ],
            // Party agents register members and report results from their ward / polling unit.
            RoleName::PARTY_AGENT->value => [
                'dashboard' => ['view', 'export'], // opens the management layout and the scoped dashboard
                'members' => self::EDIT,
                'polling-units' => ['view'],
                'results' => ['view', 'upload'],
            ],
        ];
    }

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->matrix() as $roleName => $modules) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) {
                $this->command?->warn("Role '{$roleName}' not found, skipped.");
                continue;
            }

            $names = [];
            foreach ($modules as $module => $actions) {
                foreach ($actions as $action) {
                    $names[] = "{$module}.{$action}";
                }
            }

            foreach ($names as $name) {
                Permission::findOrCreate($name, $role->guard_name);
            }

            $role->givePermissionTo($names);
            $this->command?->info("{$roleName}: " . count($names) . ' permissions ensured.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
