<?php

namespace App\Policies;

use App\Models\CampaignTeam;
use App\Models\User;

class CampaignTeamPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->checkPermissionTo('campaign-teams.view');
    }

    public function view(User $u, CampaignTeam $t): bool
    {
        return $this->manageTeam($u, $t) || $this->isLead($u, $t) || $this->isMember($u, $t);
    }

    /** authorize('create', [CampaignTeam::class, $aspirantId]) */
    public function create(User $u, ?string $aspirantId = null): bool
    {
        return $u->checkPermissionTo('campaign-teams.manage')
            || ($aspirantId && \App\Models\Aspirant::whereKey($aspirantId)->where('user_id', $u->id)->exists());
    }

    public function update(User $u, CampaignTeam $t): bool
    {
        return $this->manageTeam($u, $t);
    }

    public function delete(User $u, CampaignTeam $t): bool
    {
        return $this->manageTeam($u, $t);
    }

    /** Add / edit / remove roster entries. The team lead can do this for their own team. */
    public function manageRoster(User $u, CampaignTeam $t): bool
    {
        return $this->manageTeam($u, $t) || $this->isLead($u, $t);
    }

    private function manageTeam(User $u, CampaignTeam $t): bool
    {
        return $u->checkPermissionTo('campaign-teams.manage') || $t->aspirant?->user_id === $u->id;
    }

    private function isLead(User $u, CampaignTeam $t): bool
    {
        return $t->team_lead_id === $u->id;
    }

    private function isMember(User $u, CampaignTeam $t): bool
    {
        return $t->members()->where('user_id', $u->id)->exists();
    }
}