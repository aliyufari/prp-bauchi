<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\CampaignTeam;
use App\Models\Pu;
use App\Models\Result;
use App\Models\User;
use App\Models\UserProfile;
use App\Observers\NormalizeLocationObserver;
use App\Support\Jurisdiction;
use App\Support\RoleHierarchy;
use ArrayObject;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AccessServiceProvider extends ServiceProvider
{
    private const UUID = '[0-9a-fA-F\-]{36}';
    
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->scoped('jurisdiction.cache', fn () => new ArrayObject());
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        UserProfile::observe(NormalizeLocationObserver::class);
        Result::observe(NormalizeLocationObserver::class);

        $this->registerManagedUserGates();
        $this->registerAspirantGates();
        $this->registerBindings();
    }

    /** Modules whose records are Users, filtered by role + jurisdiction. */
    private function managed(): array
    {
        return [
            'members'            => ['role' => Role::MEMBER->value,            'param' => 'member'],
            'party-agents'       => ['role' => Role::PARTY_AGENT->value,       'param' => 'party_agent'],
            'coordinators'       => ['role' => Role::COORDINATOR->value,       'param' => 'coordinator'],
            'stakeholders'       => ['role' => Role::STAKEHOLDER->value,       'param' => 'stakeholder'],
            'returning-officers' => ['role' => Role::RETURNING_OFFICER->value, 'param' => 'returning_officer'],
            'supervisory-agents' => ['role' => Role::SUPERVISORY_AGENT->value, 'param' => 'supervisory_agent'],
        ];
    }

    /**
     * Gate names use a COLON ("members:update"). Spatie registers a gate for every
     * permission name ("members.update") that returns true as soon as the user holds
     * the permission, which would skip our jurisdiction checks. Never reuse that name.
     */
    private function registerManagedUserGates(): void
    {
        foreach ($this->managed() as $module => $cfg) {
            Gate::define("{$module}:viewAny", fn (User $a) => $a->checkPermissionTo("{$module}.view"));
            Gate::define("{$module}:create", fn (User $a) => $a->checkPermissionTo("{$module}.create"));
            Gate::define("{$module}:export", fn (User $a) => $a->checkPermissionTo("{$module}.export"));

            foreach (['view', 'update', 'delete'] as $action) {
                Gate::define("{$module}:{$action}", function (User $actor, User $target) use ($module, $cfg, $action) {
                    if (!$actor->checkPermissionTo("{$module}.{$action}")) {
                        return false;
                    }
                    if (!$target->hasRole($cfg['role'])) {
                        return false;
                    }
                    if ($action === 'delete' && $actor->is($target)) {
                        return false;
                    }
                    if (!Jurisdiction::for($actor)->coversUser($target)) {
                        return false;
                    }

                    return $action === 'view' || RoleHierarchy::canManage($actor, $target);
                });
            }
        }
    }

    /** Aspirants are campaign-owned, not geographic. */
    private function registerAspirantGates(): void
    {
        Gate::define('aspirants:viewAny', fn (User $a) => $a->checkPermissionTo('aspirants.view'));
        Gate::define('aspirants:manage', fn (User $a) => $a->checkPermissionTo('aspirants.manage'));
        Gate::define('aspirants:view', fn (User $a, User $t) => $a->checkPermissionTo('aspirants.view') || $a->is($t));
    }

    private function registerBindings(): void
    {
        $viewer = fn () => request()->user() ?? abort(401);

        foreach ($this->managed() as $cfg) {
            Route::pattern($cfg['param'], self::UUID);
            Route::bind($cfg['param'], fn (string $v) => User::role($cfg['role'])->visibleTo($viewer())->findOrFail($v));
        }

        foreach (['polling_unit', 'campaign_team', 'result', 'team_member'] as $param) {
            Route::pattern($param, self::UUID);
        }

        Route::bind('polling_unit', fn (string $v) => Pu::visibleTo($viewer())->findOrFail($v));
        Route::bind('campaign_team', fn (string $v) => CampaignTeam::visibleTo($viewer())->findOrFail($v));
        Route::bind('result', fn (string $v) => Result::visibleTo($viewer())->findOrFail($v));
    }
}
