<?php

namespace App\Http\Requests\PartyAgent;

use App\Enums\EducationStatus;
use Illuminate\Validation\Rule;

use App\Enums\PartyAgentType;
use App\Traits\ValidatesLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class StorePartyAgentRequest extends FormRequest
{
    use ValidatesLocation {
        ValidatesLocation::withValidator as validateLocation;
    }

    public function authorize(): bool
    {
        return Gate::allows('party-agents:create');
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
            'type' => ['required', new Enum(PartyAgentType::class)],
        ] + $this->locationRules();
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateLocation($validator);

        // Party agents work at a ward or a polling unit; nothing higher grants them access.
        $validator->after(function (Validator $v) {
            if (!$this->filled('ward_id') && !$this->filled('pu_id')) {
                $v->errors()->add('ward_id', 'Select the ward or polling unit this agent will cover.');
            }
        });
    }
}
