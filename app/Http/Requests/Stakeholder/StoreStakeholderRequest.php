<?php

namespace App\Http\Requests\Stakeholder;

use App\Enums\EducationStatus;
use Illuminate\Validation\Rule;

use App\Enums\StakeholderType;
use App\Traits\ValidatesLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;

class StoreStakeholderRequest extends FormRequest
{
    use ValidatesLocation;

    public function authorize(): bool
    {
        return Gate::allows('stakeholders:create');
    }

    public function rules(): array
    {
        return [
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'string'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'education_status' => ['nullable', Rule::enum(EducationStatus::class)],
            'application_id' => ['nullable', 'string', 'max:255'],
            'type' => ['required', new Enum(StakeholderType::class)],
            'organization' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
        ] + $this->locationRules();
    }
}