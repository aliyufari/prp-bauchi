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
            'code' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('pus', 'code')->where(fn ($query) => $query->where('ward_id', $this->input('ward_id')))],
            'ward_id' => ['required', 'uuid', 'exists:wards,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'registered_voters' => ['nullable', 'integer', 'min:0'],
        ];
    }
}