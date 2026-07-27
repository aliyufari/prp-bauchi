<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignTeamMember extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'campaign_team_id',
        'user_id',
        'role_in_team',
        'is_active',
        'joined_date',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'joined_date' => 'date',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(CampaignTeam::class, 'campaign_team_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}