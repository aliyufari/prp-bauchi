<?php

namespace App\Http\Requests\Pu;

use Illuminate\Foundation\Http\FormRequest;

class StorePuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'ward_id' => ['nullable', 'uuid', 'exists:wards,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'registered_voters' => ['nullable', 'integer', 'min:0'],
        ];
    }
}