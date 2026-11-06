<?php

namespace App\Observers;

use App\Support\LocationChain;
use Illuminate\Validation\ValidationException;
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

        $complete = LocationChain::complete($ids);
        $bauchiId = LocationChain::bauchiStateId();

        if (!$bauchiId || ($complete['state_id'] ?? null) !== $bauchiId) {
            throw ValidationException::withMessages([
                'state_id' => 'This application is configured for Bauchi State. Select a location within Bauchi.',
            ]);
        }

        foreach ($complete as $column => $value) {
            if ($value) {
                $model->{$column} = $value;
            }
        }
    }
}