<?php

namespace App\Http\Requests\Member;

use App\Traits\ValidatesLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreMemberRequest extends FormRequest
{
    use ValidatesLocation;

    public function authorize(): bool
    {
        return Gate::allows('members:create');
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'string'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'education_status' => ['nullable', 'string'],
            'application_id' => ['nullable', 'string', 'max:255'],
            'mentor_name' => ['nullable', 'string', 'max:255'],
            'mentor_status' => ['boolean'],
            'training_status' => ['boolean'],
        ] + $this->locationRules();
    }
}