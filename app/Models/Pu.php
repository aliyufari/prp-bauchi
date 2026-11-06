<?php

namespace App\Models;

use App\Support\Jurisdiction;
use App\Support\LocationChain;
use App\Traits\HasJurisdiction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Pu extends Model
{
    use HasUuids, HasJurisdiction;

    protected $fillable = [
        'code',
        'name',
        'ward_id'
    ];

    protected function casts(): array
    {
        return [
            'registered_voters' => 'integer',
        ];
    }

    protected function constrainToOperatingState(Builder $query, string $stateId): Builder
    {
        return $query->whereIn(
            $this->qualifyColumn('id'),
            LocationChain::within('pu', 'state', $stateId),
        );
    }

    protected function constrainToJurisdiction(Builder $query, Jurisdiction $j): Builder
    {
        return $query->whereIn($this->qualifyColumn('id'), LocationChain::within('pu', $j->level, $j->id));
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function users(): MorphMany
    {
        return $this->morphMany(User::class, 'location');
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(UserProfile::class, 'pu_id');
    }

    public function results(): HasMany 
    { 
        return $this->hasMany(Result::class, 'pu_id'); 
    }
}
