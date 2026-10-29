<?php

namespace App\Http\Resources;

use App\Traits\ExposesAbilities;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PuResource extends JsonResource
{
    use ExposesAbilities;

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
            'agents_deployed' => $this->agents_count ?? 0,
            'can' => $this->abilities($request),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}