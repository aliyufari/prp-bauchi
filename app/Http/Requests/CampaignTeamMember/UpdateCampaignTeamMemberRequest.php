<?php

namespace App\Http\Requests\CampaignTeamMember;

use App\Enums\EducationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

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
    protected function prepareForValidation(): void
    {
        $educationStatus = $this->input('education_status');

        if (is_string($educationStatus)) {
            $this->merge(['education_status' => strtolower(trim($educationStatus)) ?: null]);
        }
    }

    public function rules(): array
    {
        return [
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'role_in_team' => ['required', 'string', 'max:255'],
            'education_status' => ['nullable', Rule::enum(EducationStatus::class)],
            'joined_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }
}
