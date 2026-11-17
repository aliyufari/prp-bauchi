<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\CampaignTeamMember\StoreCampaignTeamMemberRequest;
use App\Http\Requests\CampaignTeamMember\UpdateCampaignTeamMemberRequest;
use App\Models\CampaignTeam;
use App\Models\CampaignTeamMember;
use App\Models\User;
use App\Support\LocationChain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use App\Traits\StoresUserAvatar;

class CampaignTeamMemberController extends Controller
{
    use StoresUserAvatar;
    public function store(StoreCampaignTeamMemberRequest $request, CampaignTeam $campaign_team)
    {
        $this->ensureBauchiTeam($campaign_team);

        DB::transaction(function () use ($request, $campaign_team) {
            $data = $request->validated();

            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $user->assignRole(Role::CAMPAIGN_TEAM_MEMBER->value);
            $user->profile()->create([
                'phone' => $data['phone'] ?? null,
                'state_id' => $campaign_team->aspirant()->with('constituency')->firstOrFail()->constituency?->state_id,
            ]);

            $campaign_team->members()->create([
                'user_id' => $user->id,
                'role_in_team' => $data['role_in_team'],
                'joined_date' => $data['joined_date'] ?? now(),
                'is_active' => $data['is_active'] ?? true,
            ]);
            $this->syncUserAvatar($user, $request);
        });

        return back()->with('success', 'Team member added.');
    }

    public function update(UpdateCampaignTeamMemberRequest $request, CampaignTeam $campaign_team, CampaignTeamMember $team_member)
    {
        Gate::authorize('manageRoster', $campaign_team);
        $this->ensureBauchiTeam($campaign_team);
        abort_unless($team_member->campaign_team_id === $campaign_team->id, 404);

        $team_member->update($request->validated());
        $this->syncUserAvatar($team_member->user, $request);

        return back()->with('success', 'Team member updated.');
    }

    public function destroy(Request $request, CampaignTeam $campaign_team, CampaignTeamMember $team_member)
    {
        Gate::authorize('manageRoster', $campaign_team);
        $this->ensureBauchiTeam($campaign_team);
        abort_unless($team_member->campaign_team_id === $campaign_team->id, 404);

        // Removes the roster entry only. The login account is kept.
        $team_member->delete();

        return back()->with('success', 'Team member removed from the team.');
    }


    private function ensureBauchiTeam(CampaignTeam $team): void
    {
        abort_unless(
            $team->aspirant()->whereHas('constituency', fn ($q) => $q->whereNotNull('state_id')->where('state_id', LocationChain::bauchiStateId()))->exists(),
            404,
        );
    }
}
