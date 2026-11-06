<?php

namespace App\Http\Requests\SupervisoryAgent;

use App\Enums\EducationStatus;
use Illuminate\Validation\Rule;

use App\Enums\Role;
use App\Enums\SupervisoryAgentType;
use App\Traits\ValidatesAssignedLevel;
use App\Traits\ValidatesLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class StoreSupervisoryAgentRequest extends FormRequest
{
    use ValidatesAssignedLevel;
    use ValidatesLocation {
        ValidatesLocation::withValidator as validateLocation;
    }

    public function authorize(): bool
    {
        return Gate::allows('supervisory-agents:create');
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
            'type' => ['required', new Enum(SupervisoryAgentType::class)],
            'agents_supervised' => ['nullable', 'integer', 'min:0'],
        ] + $this->locationRules();
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateLocation($validator);

        $validator->after(fn (Validator $v) => $this->validateAssignedLevel($v, Role::SUPERVISORY_AGENT->value));
    }
}