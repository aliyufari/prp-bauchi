<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignTeamMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'first_name' => $this->user?->first_name,
            'last_name' => $this->user?->last_name,
            'email' => $this->user?->email,
            'phone' => $this->user?->profile?->phone,

            'role_in_team' => $this->role_in_team,
            'status' => $this->is_active ? 'active' : 'inactive',
            'joined_date' => $this->joined_date?->toDateString(),

            'campaign_team_id' => $this->campaign_team_id,

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
