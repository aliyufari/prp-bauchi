<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserManagementActionController extends Controller
{
    private const ROLES = [
        'party-agents' => Role::PARTY_AGENT, 'coordinators' => Role::COORDINATOR,
        'stakeholders' => Role::STAKEHOLDER, 'returning-officers' => Role::RETURNING_OFFICER,
        'supervisory-agents' => Role::SUPERVISORY_AGENT, 'members' => Role::MEMBER,
        'aspirants' => Role::ASPIRANT,
    ];

    public function status(Request $request, string $module, User $user)
    {
        abort_unless(isset(self::ROLES[$module]), 404);
        abort_unless($user->hasRole(self::ROLES[$module]->value), 404);
        if ($module === 'aspirants') {
            Gate::authorize('aspirants:manage');
        } else {
            Gate::authorize("{$module}:update", $user);
        }
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $user->forceFill(['is_active' => $data['is_active']])->save();

        return back()->with('success', $data['is_active'] ? 'Account reactivated successfully.' : 'Account suspended successfully.');
    }
}
