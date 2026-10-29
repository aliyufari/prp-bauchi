<?php

namespace App\Policies;

use App\Enums\ResultStatus;
use App\Models\Result;
use App\Models\User;
use App\Support\Jurisdiction;

class ResultPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->checkPermissionTo('results.view');
    }

    public function view(User $u, Result $r): bool
    {
        return $this->viewAny($u) && $this->inScope($u, $r);
    }

    /** authorize('upload', [Result::class, ['pu_id' => ...]]) */
    public function upload(User $u, array $location): bool
    {
        return $u->checkPermissionTo('results.upload') && Jurisdiction::for($u)->covers($location);
    }

    public function update(User $u, Result $r): bool
    {
        if (!$u->checkPermissionTo('results.upload') || !$this->inScope($u, $r)) {
            return false;
        }

        // Coordinators may only touch pending results; admins may touch any.
        return Jurisdiction::for($u)->unrestricted || $r->status === ResultStatus::PENDING;
    }

    public function delete(User $u, Result $r): bool
    {
        return $this->update($u, $r);
    }

    public function verify(User $u, Result $r): bool
    {
        return $u->checkPermissionTo('results.verify') && $this->inScope($u, $r);
    }

    private function inScope(User $u, Result $r): bool
    {
        return Jurisdiction::for($u)->covers(
            $r->only(['state_id', 'zone_id', 'lga_id', 'ward_id', 'pu_id']),
            complete: false,
        );
    }
}