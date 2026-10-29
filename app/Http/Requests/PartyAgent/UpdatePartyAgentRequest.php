<?php

namespace App\Http\Requests\PartyAgent;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdatePartyAgentRequest extends StorePartyAgentRequest
{
    public function authorize(): bool
    {
        return Gate::allows('party-agents:update', $this->route('party_agent'));
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->route('party_agent')?->id)],
            'password' => ['nullable', 'string', 'min:8'],
        ] + parent::rules();
    }
}