<?php

namespace App\Traits;

use App\Models\User;
use App\Support\Jurisdiction;
use App\Support\LocationChain;
use Illuminate\Database\Eloquent\Builder;

trait HasJurisdiction
{
    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        $stateId = LocationChain::bauchiStateId();
        if (!$stateId) {
            return $query->whereRaw('1 = 0');
        }

        $query = $this->constrainToOperatingState($query, $stateId);
        $j = Jurisdiction::for($viewer);

        if ($j->unrestricted) {
            return $query;
        }
        if (!$j->hasAccess()) {
            return $query->whereRaw('1 = 0');
        }

        return $this->constrainToJurisdiction($query, $j);
    }

    /** Override when the model reaches its state through a relationship. */
    protected function constrainToOperatingState(Builder $query, string $stateId): Builder
    {
        return $query->where($this->qualifyColumn('state_id'), $stateId);
    }

    /** Default: the model carries state_id..pu_id columns itself (e.g. Result). */
    protected function constrainToJurisdiction(Builder $query, Jurisdiction $j): Builder
    {
        return $query->where($this->qualifyColumn($j->column()), $j->id);
    }
}