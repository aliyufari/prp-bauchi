<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;

/**
 * Nobody may create or edit anyone at or above their own rank.
 * Coordinators outrank each other by level: state > zone > lga > ward.
 */
final class RoleHierarchy
{
    private const LEVEL_WEIGHT = ['state' => 4, 'zone' => 3, 'lga' => 2, 'ward' => 1, 'pu' => 0];

    public static function rankForRole(string $role, ?string $level = null): int
    {
        $w = self::LEVEL_WEIGHT[$level ?? 'pu'] * 10;

        return match ($role) {
            Role::SUPER_ADMIN->value => 1000,
            Role::ADMIN->value => 900,
            Role::COORDINATOR->value => 500 + $w,
            Role::SUPERVISORY_AGENT->value => 400 + $w,
            Role::RETURNING_OFFICER->value => 300,
            Role::STAKEHOLDER->value => 250,
            Role::PARTY_AGENT->value => 200,
            Role::ASPIRANT->value => 150,
            Role::CAMPAIGN_TEAM_MEMBER->value => 120,
            Role::MEMBER->value => 100,
            default => 0,
        };
    }

    public static function rankOf(User $user): int
    {
        $level = Jurisdiction::for($user)->level;

        return $user->getRoleNames()->map(fn ($r) => self::rankForRole($r, $level))->max() ?? 0;
    }

    public static function canManage(User $actor, User $target): bool
    {
        if ($actor->hasRole(Role::SUPER_ADMIN->value)) {
            return true;
        }

        return self::rankOf($actor) > self::rankOf($target);
    }

    /** May $actor create a user with this role (and level, for coordinators/supervisors)? */
    public static function canAssign(User $actor, string $role, ?string $level = null): bool
    {
        if ($actor->hasRole(Role::SUPER_ADMIN->value)) {
            return true;
        }

        return self::rankOf($actor) > self::rankForRole($role, $level);
    }
}