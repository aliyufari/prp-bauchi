<?php

namespace App\Http\Requests\ReturningOfficer;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateReturningOfficerRequest extends StoreReturningOfficerRequest
{
    public function authorize(): bool
    {
        return Gate::allows('returning-officers:update', $this->route('returning_officer'));
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->route('returning_officer')?->id)],
            'password' => ['nullable', 'string', 'min:8'],
        ] + parent::rules();
    }
}