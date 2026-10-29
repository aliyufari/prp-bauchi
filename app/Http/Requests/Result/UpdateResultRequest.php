<?php

namespace App\Http\Requests\Result;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpdateResultRequest extends StoreResultRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('result'));
    }

    public function rules(): array
    {
        return $this->commonRules(); // the polling unit can't change after upload
    }

    protected function checkJurisdiction(Validator $v): void
    {
        // Location is fixed on update; the policy already checked it.
    }
}