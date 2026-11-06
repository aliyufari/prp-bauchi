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
        $stateId = LocationChain::bauchiStateId();
        $out = [];

        if (!$stateId) {
            return ['states' => collect(), 'zones' => collect(), 'lgas' => collect(), 'wards' => collect()];
        }

        foreach (['state' => 'states', 'zone' => 'zones', 'lga' => 'lgas', 'ward' => 'wards'] as $level => $key) {
            $q = DB::table(LocationChain::TABLES[$level])->orderBy('name');
            if ($level === 'state') {
                $q->where('id', $stateId);
            } else {
                $q->whereIn('id', LocationChain::within($level, 'state', $stateId));
            }
            $columns = $level === 'ward' ? ['id', 'name', 'lga_id'] : ['id', 'name'];
            $out[$key] = $j->constrainTable($level, $q)->get($columns);
        }

        return $out;
    }
}