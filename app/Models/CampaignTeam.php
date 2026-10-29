<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;

class CampaignTeam extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'aspirant_id',
        'team_lead_id',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function aspirant(): BelongsTo
    {
        return $this->belongsTo(Aspirant::class);
    }

    public function teamLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CampaignTeamMember::class);
    }

    public function scopeVisibleTo(Builder $q, User $u): Builder
    {
        if ($u->checkPermissionTo('campaign-teams.manage')) {
            return $q;
        }

        return $q->where(fn ($w) => $w
            ->where('team_lead_id', $u->id)
            ->orWhereHas('members', fn ($m) => $m->where('user_id', $u->id))
            ->orWhereHas('aspirant', fn ($a) => $a->where('user_id', $u->id)));
    }

    /**
     * Whether the given user may manage this team's roster —
     * either they're the assigned team lead, or they hold an
     * admin-level role. Kept as a plain method rather than a
     * full Policy class since the rule is this simple for now.
     */
    // public function canBeManagedBy(User $user): bool
    // {
    //     if ($user->hasAnyRole(['super_admin', 'admin'])) {
    //         return true;
    //     }

    //     return $this->team_lead_id === $user->id;
    // }

    public function canBeManagedBy(User $user): bool
    {
        return Gate::forUser($user)->allows('manageRoster', $this);
    }
}