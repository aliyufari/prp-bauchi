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
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function official(): HasOne
    {
        return $this->hasOne(PuOfficial::class, 'pu_id');
    }

    /** All evidence submissions of this PU (one per office). */
    public function submissions(): HasMany
    {
        return $this->hasMany(PuResultSubmission::class, 'pu_id');
    }

    /**
     * Eager-load / read the submission of one office:
     *   Pu::with(['submission' => fn ($q) => $q->where('office', $office)])
     */
    public function submission(): HasOne
    {
        return $this->hasOne(PuResultSubmission::class, 'pu_id');
    }

    /** Counting video and at least one result-sheet photo uploaded for the office. */
    public function scopeResultComplete(Builder $query, string $office): Builder
    {
        return $query->whereHas('submission', fn ($s) => $s->where('office', $office)
            ->whereNotNull('video_path')->whereRaw('json_length(sheets) > 0'));
    }

    public function scopeResultIncomplete(Builder $query, string $office): Builder
    {
        return $query->where(fn ($q) => $q
            ->whereDoesntHave('submission', fn ($s) => $s->where('office', $office))
            ->orWhereHas('submission', fn ($s) => $s->where('office', $office)->where(fn ($s) => $s
                ->whereNull('video_path')->orWhereRaw('sheets is null or json_length(sheets) = 0'))));
    }
}
