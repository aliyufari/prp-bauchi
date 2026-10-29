<?php

namespace App\Services;

use App\Enums\CoordinatorType;
use App\Models\User;

class ResultAuthorizationService
{
    /**
     * @param array{state_id?:?string,zone_id?:?string,lga_id?:?string,ward_id?:?string,pu_id?:?string} $location
     */
    public function canUpload(User $user, array $location): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if (!$user->hasRole('coordinator')) {
            return false;
        }

        $coordinator = $user->coordinator;
        $profile = $user->profile;

        if (!$coordinator || !$profile) {
            return false;
        }

        $field = $this->jurisdictionField($coordinator->type);

        if (!$field || !$profile->{$field}) {
            return false;
        }

        return isset($location[$field])
            && (string) $profile->{$field} === (string) $location[$field];
    }

    /**
     * Maps a coordinator's type to the profile/location column that
     * defines their jurisdiction. Uses keyword matching rather than an
     * exact case list, so it still works even if your CoordinatorType
     * enum uses slightly different case names than assumed here.
     */
    public function jurisdictionField(CoordinatorType $type): ?string
    {
        $name = strtoupper($type->value);

        return match (true) {
            str_contains($name, 'STATE') => 'state_id',
            str_contains($name, 'ZONE'), str_contains($name, 'ZONAL') => 'zone_id',
            str_contains($name, 'LGA') => 'lga_id',
            str_contains($name, 'WARD') => 'ward_id',
            default => null,
        };
    }
}