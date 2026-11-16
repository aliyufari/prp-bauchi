<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AspirantOffice;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Pu;
use App\Models\User;
use App\Support\Jurisdiction;
use App\Support\LocationChain;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Pages every signed-in portal user gets: dashboard, profile, and (party agents) team of agents. */
class PortalController extends Controller
{
    public function home(Request $request)
    {
        $user = $this->portalUser($request);
        $office = $this->office($request);
        $tasks = null;

        if ($user->checkPermissionTo('pu-tasks.manage')) {
            $base = fn () => Pu::query()->visibleTo($user);
            $total = $base()->count();
            $officials = $base()->whereHas('official')->count();
            $results = $base()->resultComplete($office->value)->count();
            $tasks = [
                'scope' => Jurisdiction::for($user)->level === 'pu' ? 'pu' : 'ward',
                'total' => $total,
                'officials' => $officials,
                'results' => $results,
                'outstanding' => $base()->resultIncomplete($office->value)->count(),
            ];
        }

        return Inertia::render('portal/Home', [
            'profile' => $this->profileBlock($user),
            'team' => $user->campaignTeamMembership?->team?->only(['id', 'name']),
            'ledTeams' => $user->ledCampaignTeams()->get(['id', 'name']),
            'tasks' => $tasks,
            'office' => $office->value,
            'offices' => $this->officeOptions(),
        ]);
    }

    public function profile(Request $request)
    {
        $user = $this->portalUser($request);
        $p = $user->profile;

        return Inertia::render('portal/Profile', [
            'me' => $this->profileBlock($user) + [
                'email' => $user->email,
                'phone' => $p?->phone,
                'gender' => $p?->gender,
                'occupation' => $p?->occupation,
                'education' => $p?->education_status,
                'avatar_url' => $user->avatar_url,
            ],
            'team' => $user->campaignTeamMembership?->team?->only(['id', 'name']),
        ]);
    }

    /** Ward party agent: the PU agents in his ward and how far each has got. */
    public function agents(Request $request)
    {
        $user = $this->portalUser($request);
        abort_unless($user->checkPermissionTo('pu-tasks.manage') && Jurisdiction::for($user)->level === 'ward', 403);
        $office = $this->office($request);

        $agents = User::query()->role(Role::PARTY_AGENT->value)->visibleTo($user)
            ->whereKeyNot($user->id)
            ->with(['profile.pu.official', 'profile.pu.submission' => fn ($q) => $q->where('office', $office->value)])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")))
            ->orderBy('first_name')->get();

        return Inertia::render('portal/Agents', [
            'office' => $office->value,
            'offices' => $this->officeOptions(),
            'search' => $request->search,
            'agents' => $agents->map(function (User $a) {
                $pu = $a->profile?->pu;
                $s = $pu?->submission;

                return [
                    'id' => $a->id,
                    'name' => trim("{$a->first_name} {$a->last_name}"),
                    'phone' => $a->profile?->phone,
                    'avatar_url' => $a->avatar_url,
                    'pu' => $pu ? ['id' => $pu->id, 'name' => $pu->name, 'code' => $pu->code] : null,
                    'officials_done' => (bool) $pu?->official,
                    'result_done' => (bool) ($s?->video_path && count($s->sheets ?? []) > 0),
                ];
            })->values(),
        ]);
    }

    public function team(Request $request)
    {
        $user = $this->portalUser($request);
        $team = $user->ledCampaignTeams()->first() ?? $user->campaignTeamMembership?->team;

        return $team ? redirect()->route('campaign-teams.show', $team->id) : redirect()->route('portal.home');
    }

    /* ------------------------------------------------------------------ */

    private function portalUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user->checkPermissionTo('portal.access'), 403);

        return $user;
    }

    private function profileBlock(User $user): array
    {
        $profile = $user->profile;

        return [
            'name' => trim("{$user->first_name} {$user->last_name}"),
            'application_id' => $profile?->application_id,
            'location' => collect(LocationChain::LEVELS)
                ->mapWithKeys(fn ($l) => [$l => LocationChain::name($l, $profile?->{"{$l}_id"})])
                ->filter(),
        ];
    }

    private function office(Request $request): AspirantOffice
    {
        return AspirantOffice::tryFrom((string) $request->query('office')) ?? AspirantOffice::GOVERNOR;
    }

    private function officeOptions(): array
    {
        return collect(AspirantOffice::cases())->map(fn ($o) => [
            'value' => $o->value, 'label' => ucwords(str_replace('_', ' ', $o->value)),
        ])->all();
    }
}
