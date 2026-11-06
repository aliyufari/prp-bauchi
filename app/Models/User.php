<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Support\Jurisdiction;
use App\Traits\HasJurisdiction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids, HasRoles, HasJurisdiction;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'avatar',
        'email',
        'email_verified_at',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            // 'password' => 'hashed',
        ];
    }

    protected function constrainToOperatingState(Builder $query, string $stateId): Builder
    {
        return $query->whereHas('profile', fn ($profile) => $profile->where('state_id', $stateId));
    }

    protected function constrainToJurisdiction(Builder $query, Jurisdiction $j): Builder
    {
        return $query->whereHas('profile', fn ($p) => $p->where($j->column(), $j->id));
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function aspirant(): HasOne
    {
        return $this->hasOne(Aspirant::class);
    }

    public function coordinator(): HasOne
    {
        return $this->hasOne(Coordinator::class);
    }

    public function partyAgent(): HasOne
    {
        return $this->hasOne(PartyAgent::class);
    }

    public function returningOfficer(): HasOne
    {
        return $this->hasOne(ReturningOfficer::class);
    }

    public function stakeholder(): HasOne
    {
        return $this->hasOne(Stakeholder::class);
    }

    public function supervisoryAgent(): HasOne
    {
        return $this->hasOne(SupervisoryAgent::class);
    }

    public function campaignTeamMembership(): HasOne
    {
        return $this->hasOne(CampaignTeamMember::class);
    }   
    
    public function ledCampaignTeams(): HasMany
    {
        return $this->hasMany(CampaignTeam::class, 'team_lead_id');
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => match (true) {
            blank($this->avatar) => null,
            str_starts_with($this->avatar, 'http') => $this->avatar,
            default => Storage::disk('public')->url($this->avatar),
        });
    }
}
