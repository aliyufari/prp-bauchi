<?php

namespace App\Support;

use App\Enums\EducationStatus;
use Illuminate\Support\Collection;
use BackedEnum;

class ExportRows
{
    public static function people(Collection $users, string $extraRelation = '', array $extraFields = []): array
    {
        return $users->map(function ($user) use ($extraRelation, $extraFields) {
            $profile = $user->profile;
            $row = [
                trim(implode(' ', array_filter([$user->first_name, $user->middle_name, $user->last_name]))),
                $user->email,
                $profile?->phone,
                $profile?->gender,
                $profile?->occupation,
                ($profile?->education_status instanceof EducationStatus) ? $profile->education_status->label() : (is_string($profile?->education_status) ? (EducationStatus::tryFrom($profile->education_status)?->label() ?? $profile->education_status) : null),
                $profile?->lga?->name,
                $profile?->ward?->name,
                $profile?->pu?->name,
                $user->is_active ? 'Active' : 'Inactive',
            ];

            if ($extraRelation !== '') {
                $related = $user->{$extraRelation};
                foreach ($extraFields as $field) {
                    $value = $related?->{$field};
                    $row[] = $value instanceof BackedEnum ? $value->value : $value;
                }
            }

            return $row;
        })->all();
    }
}
