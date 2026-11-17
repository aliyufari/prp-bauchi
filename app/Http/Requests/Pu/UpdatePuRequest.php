<?php

namespace App\Http\Requests\Pu;

use App\Models\Pu;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePuRequest extends StorePuRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('polling_unit'));
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:50', Rule::unique('pus', 'code')->where(fn ($query) => $query->where('ward_id', $this->input('ward_id', $this->route('polling_unit')?->ward_id)))->ignore($this->route('polling_unit')?->id)]]
            + parent::rules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (!$v->errors()->has('ward_id') && !Gate::allows('create', [Pu::class, $this->input('ward_id')])) {
                $v->errors()->add('ward_id', 'That ward is outside your jurisdiction.');
            }
        });
    }
}