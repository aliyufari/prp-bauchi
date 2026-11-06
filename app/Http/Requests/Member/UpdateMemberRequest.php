<?php

namespace App\Http\Requests\Member;

use App\Enums\EducationStatus;
use App\Traits\ValidatesLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    use ValidatesLocation;

    public function authorize(): bool
    {
        return Gate::allows('members:update', $this->route('member'));
    }

    protected function prepareForValidation(): void
    {
        $educationStatus = $this->input('education_status');

        if (is_string($educationStatus)) {
            $this->merge(['education_status' => strtolower(trim($educationStatus))]);
        }
    }

    public function rules(): array
    {
        return [
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->route('member')?->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'string'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'education_status' => ['nullable', Rule::enum(EducationStatus::class)],
            'application_id' => ['nullable', 'string', 'max:255'],
            'mentor_name' => ['nullable', 'string', 'max:255'],
            'mentor_status' => ['boolean'],
            'training_status' => ['boolean'],
        ] + $this->locationRules();
    }
}