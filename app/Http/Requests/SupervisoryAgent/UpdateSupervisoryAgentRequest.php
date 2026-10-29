<?php

namespace App\Http\Requests\SupervisoryAgent;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateSupervisoryAgentRequest extends StoreSupervisoryAgentRequest
{
    public function authorize(): bool
    {
        return Gate::allows('supervisory-agents:update', $this->route('supervisory_agent'));
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->route('supervisory_agent')?->id)],
            'password' => ['nullable', 'string', 'min:8'],
        ] + parent::rules();
    }
}