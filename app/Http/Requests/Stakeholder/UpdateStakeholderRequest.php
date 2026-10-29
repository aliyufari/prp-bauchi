<?php

namespace App\Http\Requests\Stakeholder;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateStakeholderRequest extends StoreStakeholderRequest
{
    public function authorize(): bool
    {
        return Gate::allows('stakeholders:update', $this->route('stakeholder'));
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->route('stakeholder')?->id)],
            'password' => ['nullable', 'string', 'min:8'],
        ] + parent::rules();
    }
}