<?php

namespace App\Observers;

use App\Support\LocationChain;
use Illuminate\Database\Eloquent\Model;

/** Keeps state_id..pu_id consistent on UserProfile and Result. */
final class NormalizeLocationObserver
{
    public function saving(Model $model): void
    {
        $ids = [];
        foreach (LocationChain::LEVELS as $l) {
            $ids["{$l}_id"] = $model->{"{$l}_id"};
        }

        if (!array_filter($ids)) {
            return;
        }

        foreach (LocationChain::complete($ids) as $column => $value) {
            if ($value) {
                $model->{$column} = $value;
            }
        }
    }
}