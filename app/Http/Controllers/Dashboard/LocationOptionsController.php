<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\Jurisdiction;
use App\Support\LocationChain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationOptionsController extends Controller
{
    public function states(Request $r) { return $this->options($r, 'state'); }
    public function zones(Request $r)  { return $this->options($r, 'zone'); }
    public function lgas(Request $r)   { return $this->options($r, 'lga'); }
    public function wards(Request $r)  { return $this->options($r, 'ward'); }
    public function pus(Request $r)    { return $this->options($r, 'pu'); }

    /**
     * Flat option list for one level, limited to Bauchi and the signed-in user's jurisdiction.
     *
     * Parent ids are OPTIONAL narrowing filters (`zone_id` on lgas, `lga_id` on wards,
     * `ward_id` on pus, ...). Without them the whole level is returned, so a ward-level
     * person can be created by picking a ward directly, with no zone/LGA selection.
     */
    private function options(Request $request, string $level)
    {
        $stateId = LocationChain::bauchiStateId();
        abort_unless($stateId, 503, 'Bauchi state locations have not been seeded.');

        $request->validate(array_fill_keys(
            array_map(fn ($l) => "{$l}_id", LocationChain::LEVELS),
            ['nullable', 'uuid'],
        ));

        $table = LocationChain::TABLES[$level];
        $query = DB::table($table)->orderBy("{$table}.name");

        if ($level === 'state') {
            $query->where("{$table}.id", $stateId);
        } else {
            $query->whereIn("{$table}.id", LocationChain::within($level, 'state', $stateId));
        }

        // Optional narrowing by any ancestor that was supplied (state_id is already implied).
        $index = LocationChain::index($level);
        foreach (array_slice(LocationChain::LEVELS, 1, max(0, $index - 1)) as $ancestor) {
            if ($id = $request->input("{$ancestor}_id")) {
                $query->whereIn("{$table}.id", LocationChain::within($level, $ancestor, $id));
            }
        }

        // Ancestors collapse to the user's own node; descendants are limited to their subtree.
        Jurisdiction::for($request->user())->constrainTable($level, $query);

        // Each row also carries its direct parent id so the client can complete the chain.
        $parent = LocationChain::parentOf($level);
        $columns = ["{$table}.id", "{$table}.name"];
        if ($level === 'pu') {
            array_push($columns, 'pus.code', 'pus.registered_voters');
        }
        if ($parent && $level !== 'zone') {
            $columns[] = "{$table}.{$parent}_id";
        }

        return response()->json($query->get($columns));
    }
}
