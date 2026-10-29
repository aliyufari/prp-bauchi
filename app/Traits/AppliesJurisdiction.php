<?php

namespace App\Traits;

use App\Support\Jurisdiction;
use Illuminate\Http\Request;

trait AppliesJurisdiction
{
    /**
     * Force the levels the user is fixed to into the filters, so a hand-edited
     * query string (?state=...) can never widen what they see.
     */
    protected function enforceFilters(Request $request): void
    {
        $locked = Jurisdiction::for($request->user())->lockedIds();

        $request->merge(array_combine(
            array_map(fn ($k) => str_replace('_id', '', $k), array_keys($locked)),
            array_values($locked),
        ));
    }
}