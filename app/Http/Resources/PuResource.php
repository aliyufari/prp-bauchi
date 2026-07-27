<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'registered_voters' => $this->registered_voters ?? 0,

            'ward_id' => $this->ward_id,
            'ward' => $this->ward?->name,

            // Deployed = at least one party agent's profile currently
            // points at this PU. Uses the count already eager-loaded
            // by the controller (agents_count) to avoid N+1 queries.
            'agents_deployed' => $this->agents_count ?? 0,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}