<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ScopedLocations
{
    /** Flat option lists for filter bars, limited to the user's jurisdiction. */
    public static function options(User $user): array
    {
        $j = Jurisdiction::for($user);
        $out = [];

        foreach (['state' => 'states', 'zone' => 'zones', 'lga' => 'lgas', 'ward' => 'wards'] as $level => $key) {
            $q = DB::table(LocationChain::TABLES[$level])->orderBy('name');
            $out[$key] = $j->constrainTable($level, $q)->get(['id', 'name']);
        }

        return $out;
    }
}