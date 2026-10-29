<?php

namespace App\Http\Requests\Pu;

use App\Models\Pu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StorePuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', [Pu::class, $this->input('ward_id')]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:pus,code'],
            'ward_id' => ['required', 'uuid', 'exists:wards,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'registered_voters' => ['nullable', 'integer', 'min:0'],
        ];
    }
}