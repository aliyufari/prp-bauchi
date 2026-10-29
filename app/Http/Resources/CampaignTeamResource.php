<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class CampaignTeamResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->is_active ? 'active' : 'inactive',

            'aspirant_id' => $this->aspirant_id,
            'aspirant_name' => $this->aspirant?->user
                ? trim("{$this->aspirant->user->first_name} {$this->aspirant->user->last_name}")
                : null,
            'aspirant_office' => $this->aspirant?->office
                ? ucwords(str_replace('_', ' ', strtolower($this->aspirant->office->value)))
                : null,

            'team_lead_id' => $this->team_lead_id,
            'team_lead_name' => $this->teamLead
                ? trim("{$this->teamLead->first_name} {$this->teamLead->last_name}")
                : null,

            'member_count' => $this->members_count ?? $this->members()->count(),

            'can_manage' => $request->user() ? Gate::forUser($request->user())->allows('manageRoster', $this->resource) : false,
            'can' => $this->abilities($request),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
