<?php

namespace App\Http\Requests\ReturningOfficer;

use App\Traits\ValidatesLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreReturningOfficerRequest extends FormRequest
{
    use ValidatesLocation;

    public function authorize(): bool
    {
        return Gate::allows('returning-officers:create');
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'string'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'education_status' => ['nullable', 'string'],
            'application_id' => ['nullable', 'string', 'max:255'],
            'institution' => ['required', 'string', 'max:255'],
            'posting_location' => ['required', 'string', 'max:255'],
        ] + [
            // Access is scoped to the LGA, so it cannot be left blank.
            'lga_id' => ['required', 'uuid', 'exists:lgas,id'],
        ] + $this->locationRules();
    }
}