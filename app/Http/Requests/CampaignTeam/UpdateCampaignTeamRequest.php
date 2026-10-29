<?php

namespace App\Http\Requests\CampaignTeam;

use Illuminate\Support\Facades\Gate;

class UpdateCampaignTeamRequest extends StoreCampaignTeamRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('campaign_team'));
    }

    public function rules(): array
    {
        return $this->commonRules();
    }
}