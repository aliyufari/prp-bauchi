<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $partyVotes = $this->party_votes ?? [];
        $leadingParty = null;
        $leadingVotes = 0;

        foreach ($partyVotes as $party => $votes) {
            if ($votes > $leadingVotes) {
                $leadingVotes = $votes;
                $leadingParty = $party;
            }
        }

        $user = $request->user();

        return [
            'id' => $this->id,
            'election_id' => $this->election_id,
            'election_name' => $this->election?->name,
            'pu_id' => $this->pu_id,
            'pu' => $this->pu?->name,
            'pu_code' => $this->pu?->code,
            'ward' => $this->ward?->name,
            'lga' => $this->lga?->name,
            'zone' => $this->zone?->name,
            'state' => $this->state?->name,

            'office' => $this->office?->value,
            'office_label' => $this->office ? ucwords(str_replace('_', ' ', strtolower($this->office->value))) : null,
            'date' => $this->date?->toDateString(),

            'registered_voters' => $this->registered_voters,
            'accredited_voters' => $this->accredited_voters,
            'rejected_votes' => $this->rejected_votes,
            'party_votes' => $partyVotes,
            'total_valid_votes' => $this->total_valid_votes,

            'leading_party' => $leadingParty,
            'leading_votes' => $leadingVotes,

            'status' => $this->status?->value,
            'status_label' => $this->status ? ucfirst(strtolower($this->status->value)) : null,
            'result_sheet_url' => $this->result_sheet_url,
            'remarks' => $this->remarks,

            'uploaded_by' => $this->uploaded_by,
            'uploaded_by_name' => $this->uploader ? trim("{$this->uploader->first_name} {$this->uploader->last_name}") : null,
            'verified_by_name' => $this->verifier ? trim("{$this->verifier->first_name} {$this->verifier->last_name}") : null,
            'verified_at' => $this->verified_at?->toISOString(),

            'can_edit' => $user ? $user->can('update', $this->resource) : false,
            'can_verify' => $user ? $user->can('verify', $this->resource) : false,
            'can' => $this->abilities($request),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
