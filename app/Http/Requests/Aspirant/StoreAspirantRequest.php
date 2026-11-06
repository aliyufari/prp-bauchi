<?php

namespace App\Http\Requests\Aspirant;

use App\Enums\EducationStatus;

use App\Enums\AspirantOffice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;
use App\Support\LocationChain;

class StoreAspirantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'string'],
            'education_status' => ['nullable', Rule::enum(EducationStatus::class)],
            'office' => ['required', new Enum(AspirantOffice::class)],
            'constituency_id' => ['required', 'uuid', Rule::exists('constituencies', 'id')->where(fn ($query) => $query->whereNotNull('state_id')->where('state_id', LocationChain::bauchiStateId()))],
            'title' => ['nullable', 'string', 'max:255'],
            'vision' => ['nullable', 'string'],
            'mission' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'overall_progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'total_supporters' => ['nullable', 'integer', 'min:0'],
            'supporters_growth_this_week' => ['nullable', 'integer'],
            'campaign_team_members' => ['nullable', 'integer', 'min:0'],
        ];
    }
}