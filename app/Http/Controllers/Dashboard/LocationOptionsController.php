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

    private function options(Request $request, string $level)
    {
        $table = LocationChain::TABLES[$level];
        $query = DB::table($table)->orderBy("{$table}.name");

        if ($parent = LocationChain::parentOf($level)) {
            $param = "{$parent}_id";
            $request->validate([$param => ['required', 'uuid']]);
            $query->where("{$table}.{$param}", $request->input($param));
        }

        // Ancestors collapse to the user's own node; descendants are limited to their subtree.
        Jurisdiction::for($request->user())->constrainTable($level, $query);

        $columns = $level === 'pu'
            ? ['pus.id', 'pus.name', 'pus.code', 'pus.registered_voters']
            : ["{$table}.id", "{$table}.name"];

        return response()->json($query->get($columns));
    }
}