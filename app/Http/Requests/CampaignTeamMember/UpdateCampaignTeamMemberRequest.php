<?php

namespace App\Http\Requests\CampaignTeamMember;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignTeamMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role_in_team' => ['required', 'string', 'max:255'],
            'joined_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }
}
