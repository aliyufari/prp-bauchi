<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\LocationChain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PortalController extends Controller
{
    public function home(Request $request)
    {
        $user = $request->user();
        abort_unless($user->checkPermissionTo('portal.access'), 403);

        $profile = $user->profile;

        return Inertia::render('portal/Home', [
            'profile' => [
                'name' => trim("{$user->first_name} {$user->last_name}"),
                'application_id' => $profile?->application_id,
                'location' => collect(LocationChain::LEVELS)
                    ->mapWithKeys(fn ($l) => [$l => LocationChain::name($l, $profile?->{"{$l}_id"})])
                    ->filter(),
            ],
            'team' => $user->campaignTeamMembership?->team?->only(['id', 'name']),
            'ledTeams' => $user->ledCampaignTeams()->get(['id', 'name']),
        ]);
    }
}