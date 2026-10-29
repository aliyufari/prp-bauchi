<?php

namespace App\Models;

use App\Enums\AspirantOffice;
use App\Enums\ResultStatus;
use App\Services\ResultAuthorizationService;
use App\Traits\HasJurisdiction;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    use HasFactory, HasUuids, HasJurisdiction;

    protected $fillable = [
        'pu_id', 'ward_id', 'lga_id', 'zone_id', 'state_id',
        'office', 'election_date',
        'registered_voters', 'accredited_voters', 'rejected_votes',
        'party_votes', 'total_valid_votes',
        'status', 'result_sheet_url', 'remarks',
        'uploaded_by', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'office' => AspirantOffice::class,
            'status' => ResultStatus::class,
            'party_votes' => 'array',
            'election_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Result $result) {
            $result->total_valid_votes = collect($result->party_votes ?? [])->sum();
        });
    }

    public function pu(): BelongsTo { return $this->belongsTo(Pu::class); }
    public function ward(): BelongsTo { return $this->belongsTo(Ward::class); }
    public function lga(): BelongsTo { return $this->belongsTo(Lga::class); }
    public function zone(): BelongsTo { return $this->belongsTo(Zone::class); }
    public function state(): BelongsTo { return $this->belongsTo(State::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }

    public function canBeEditedBy(User $user): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($this->status !== ResultStatus::PENDING) {
            return false;
        }

        return app(ResultAuthorizationService::class)->canUpload($user, [
            'state_id' => $this->state_id,
            'zone_id' => $this->zone_id,
            'lga_id' => $this->lga_id,
            'ward_id' => $this->ward_id,
            'pu_id' => $this->pu_id,
        ]);
    }
}