<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * "His own people" for an aspirant: the leads and members of the campaign teams that belong to him.
 * This is the single place that decides what an aspirant's dashboard and report cover.
 * Widen or narrow it here (for example once people carry an aspirant_id).
 */
final class AspirantScope
{
    /** @var array<string, array<int, string>> */
    private static array $cache = [];

    public static function is(User $user): bool
    {
        return $user->hasRole(Role::ASPIRANT->value);
    }

    /** @return array<int, string> */
    public static function peopleIds(User $aspirant): array
    {
        return self::$cache[$aspirant->id] ??= DB::table('campaign_teams as t')
            ->join('aspirants as a', 'a.id', '=', 't.aspirant_id')
            ->leftJoin('campaign_team_members as m', 'm.campaign_team_id', '=', 't.id')
            ->where('a.user_id', $aspirant->id)
            ->get(['t.team_lead_id', 'm.user_id'])
            ->flatMap(fn ($r) => [$r->team_lead_id, $r->user_id])
            ->filter()->unique()->values()->all();
    }
}
