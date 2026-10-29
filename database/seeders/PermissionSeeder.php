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
        'aspirants.view', 'aspirants.manage',
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
        $grant(Role::ADMIN, $permission::whereNotIn('name', ['roles.manage', 'backups.manage'])->pluck('name')->all());

        // One coordinator role for every level. Reach comes from Jurisdiction, not from the role.
        $grant(Role::COORDINATOR, array_merge(
            ['dashboard.view', 'reports.view', 'campaign.view', 'polling-units.view', 'results.view', 'results.upload', 'aspirants.view'],
            $this->crud('members'),
            $this->crud('party-agents'),
            $this->crud('coordinators', ['view', 'create', 'update', 'delete']),
            $this->crud('stakeholders', ['view', 'create', 'update']),
            $this->crud('supervisory-agents', ['view', 'create', 'update']),
            $this->crud('returning-officers', ['view']),
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