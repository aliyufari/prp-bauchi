<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\CampaignTeamMember\StoreCampaignTeamMemberRequest;
use App\Http\Requests\CampaignTeamMember\UpdateCampaignTeamMemberRequest;
use App\Models\CampaignTeam;
use App\Models\CampaignTeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CampaignTeamMemberController extends Controller
{
    public function store(StoreCampaignTeamMemberRequest $request, CampaignTeam $campaign_team)
    {
        abort_unless($campaign_team->canBeManagedBy($request->user()), 403);

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
            ]);

            $campaign_team->members()->create([
                'user_id' => $user->id,
                'role_in_team' => $data['role_in_team'],
                'joined_date' => $data['joined_date'] ?? now(),
                'is_active' => $data['is_active'] ?? true,
            ]);
        });

        return back()->with('success', 'Team member added.');
    }

    public function update(UpdateCampaignTeamMemberRequest $request, CampaignTeam $campaign_team, CampaignTeamMember $member)
    {
        abort_unless($campaign_team->canBeManagedBy($request->user()), 403);
        abort_unless($member->campaign_team_id === $campaign_team->id, 404);

        $member->update($request->validated());

        return back()->with('success', 'Team member updated.');
    }

    public function destroy(Request $request, CampaignTeam $campaign_team, CampaignTeamMember $member)
    {
        abort_unless($campaign_team->canBeManagedBy($request->user()), 403);
        abort_unless($member->campaign_team_id === $campaign_team->id, 404);

        // Removes the roster entry only; the underlying User account is
        // left intact so their login/history isn't destroyed by a
        // simple team reassignment. Delete the User separately if the
        // account itself should be removed.
        $member->delete();

        return back()->with('success', 'Team member removed from the team.');
    }
}