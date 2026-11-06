<?php

namespace App\Http\Requests\CampaignTeam;

use App\Enums\Role;
use App\Models\CampaignTeam;
use App\Models\User;
use App\Support\LocationChain;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreCampaignTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', [CampaignTeam::class, $this->input('aspirant_id')]);
    }

    public function rules(): array
    {
        return ['aspirant_id' => ['required', 'uuid', Rule::exists('aspirants', 'id')->where(fn ($query) => $query->whereIn('constituency_id', DB::table('constituencies')->select('id')->whereNotNull('state_id')->where('state_id', LocationChain::bauchiStateId())))]] + $this->commonRules();
    }

    protected function commonRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'team_lead_id' => ['nullable', 'uuid', 'exists:users,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // A lead must hold the campaign role, otherwise they couldn't open the team page.
        $validator->after(function (Validator $v) {
            if ($id = $this->input('team_lead_id')) {
                if (!User::find($id)?->hasRole(Role::CAMPAIGN_TEAM_MEMBER->value)) {
                    $v->errors()->add('team_lead_id', 'The team lead must be a campaign team member account.');
                }
            }
        });
    }
}