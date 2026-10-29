<?php

namespace App\Http\Requests\Coordinator;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateCoordinatorRequest extends StoreCoordinatorRequest
{
    public function authorize(): bool
    {
        return Gate::allows('coordinators:update', $this->route('coordinator'));
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->route('coordinator')?->id)],
            'password' => ['nullable', 'string', 'min:8'],
        ] + parent::rules();
    }
}