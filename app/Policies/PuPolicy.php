<?php

namespace App\Policies;

use App\Models\Pu;
use App\Models\User;
use App\Support\Jurisdiction;

class PuPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->checkPermissionTo('polling-units.view');
    }

    public function view(User $u, Pu $pu): bool
    {
        return $this->viewAny($u) && $this->inScope($u, $pu);
    }

    /** authorize('create', [Pu::class, $wardId]) */
    public function create(User $u, ?string $wardId = null): bool
    {
        if (!$u->checkPermissionTo('polling-units.manage')) {
            return false;
        }

        $j = Jurisdiction::for($u);

        return $j->unrestricted || ($wardId && $j->covers(['ward_id' => $wardId]));
    }

    public function update(User $u, Pu $pu): bool
    {
        return $u->checkPermissionTo('polling-units.manage') && $this->inScope($u, $pu);
    }

    public function delete(User $u, Pu $pu): bool
    {
        return $this->update($u, $pu);
    }

    private function inScope(User $u, Pu $pu): bool
    {
        return Jurisdiction::for($u)->covers(['pu_id' => $pu->id, 'ward_id' => $pu->ward_id]);
    }
}