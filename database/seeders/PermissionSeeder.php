<?php

namespace Database\Seeders;

use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    private const PEOPLE = [
        'members', 'party-agents', 'coordinators', 'stakeholders', 'returning-officers', 'supervisory-agents',
    ];

    private const SINGLES = [
        'dashboard.view', 'portal.access', 'reports.view',
        'campaign.view', 'finance.view',
        'polling-units.view', 'polling-units.manage',
        'results.view', 'results.upload', 'results.verify',
        'aspirants.view', 'aspirants.manage', 'aspirants.export',
        'campaign-teams.view', 'campaign-teams.manage',
        'users.manage', 'roles.manage', 'settings.manage', 'audit-logs.view', 'backups.manage',
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $permission = $registrar->getPermissionClass();
        $roleModel = $registrar->getRoleClass();

        collect(self::PEOPLE)
            ->flatMap(fn ($module) => $this->crud($module))
            ->merge(self::SINGLES)
            ->unique()
            ->each(fn ($name) => $permission::findOrCreate($name, 'web'));

        foreach (Role::cases() as $role) {
            $roleModel::findOrCreate($role->value, 'web');
        }

        $grant = fn (Role $role, array $perms) => $roleModel::findOrCreate($role->value, 'web')->syncPermissions($perms);

        $grant(Role::SUPER_ADMIN, $permission::pluck('name')->all());
        $grant(Role::ADMIN, $permission::pluck('name')->all());

        // Coordinators manage only coordinators below their own level.
        // Their geographic reach is enforced separately by Jurisdiction and the managed-user gates.
        $grant(Role::COORDINATOR, array_merge(
            ['dashboard.view'],
            $this->crud('coordinators'),
        ));

        $grant(Role::SUPERVISORY_AGENT, [
            'dashboard.view', 'reports.view', 'polling-units.view', 'results.view',
            'members.view', 'party-agents.view', 'party-agents.update',
        ]);
        $grant(Role::RETURNING_OFFICER, ['dashboard.view', 'polling-units.view', 'results.view', 'party-agents.view']);
        $grant(Role::PARTY_AGENT, ['portal.access', 'results.view']);
        $grant(Role::MEMBER, ['portal.access']);
        $grant(Role::STAKEHOLDER, ['portal.access']);
        $grant(Role::ASPIRANT, ['portal.access', 'aspirants.view', 'campaign-teams.view']);
        $grant(Role::CAMPAIGN_TEAM_MEMBER, ['portal.access', 'campaign-teams.view']);
    }

    /** @return array<int, string> */
    private function crud(string $module, array $actions = ['view', 'create', 'update', 'delete', 'export']): array
    {
        return array_map(fn ($a) => "{$module}.{$a}", $actions);
    }
}