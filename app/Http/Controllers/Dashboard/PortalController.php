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
        $unit = null;

        if ($user->checkPermissionTo('pu-tasks.manage')) {
            $base = fn () => Pu::query()->visibleTo($user);

            $jurisdiction = Jurisdiction::for($user);

            if ($jurisdiction->level === 'pu') {
                // A polling-unit agent only ever sees his own unit.
                $pu = $base()->with([
                    'ward.lga', 'official',
                    'submission' => fn ($q) => $q->where('office', $office->value),
                    'results' => fn ($q) => $q->where('office', $office->value)->whereHas('election', fn ($e) => $e->where('is_active', true)),
                ])->first();
                $sub = $pu?->submission;
                $unit = $pu ? [
                    'id' => $pu->id, 'name' => $pu->name, 'code' => $pu->code, 'ward' => $pu->ward?->name, 'lga' => $pu->ward?->lga?->name,
                    'officials_done' => (bool) $pu->official,
                    'has_video' => (bool) $sub?->video_path,
                    'sheet_count' => count($sub?->sheets ?? []),
                    'has_figures' => $pu->results->isNotEmpty(),
                ] : null;
            } elseif ($jurisdiction->level === 'ward') {
                // Ward agents can monitor and work on every polling unit in their ward.
                $total = $base()->count();
                $done = $base()->taskDone($office->value)->count();
                $new = $base()->taskNotStarted($office->value)->count();
                $tasks = ['total' => $total, 'done' => $done, 'new' => $new, 'progress' => $total - $done - $new];
            }
        }

        return Inertia::render('portal/Home', [
            'profile' => $this->profileBlock($user),
            'team' => $user->campaignTeamMembership?->team?->only(['id', 'name']),
            'ledTeams' => $user->ledCampaignTeams()->get(['id', 'name']),
            'tasks' => $tasks,
            'unit' => $unit,
            'office' => $office->value,
            'offices' => $this->officeOptions(),
            'taskAccess' => $user->checkPermissionTo('pu-tasks.manage'),
            'jurisdictionLevel' => Jurisdiction::for($user)->level,
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
                'last_login_at' => $user->last_login_at?->toISOString(),
                'last_login_ip' => $user->last_login_ip,
                'last_login_browser' => $user->last_login_browser,
                'last_login_device' => $user->last_login_device,
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
            ->with([
                'profile.pu.official',
                'profile.pu.submission' => fn ($q) => $q->where('office', $office->value),
                'profile.pu.results' => fn ($q) => $q->where('office', $office->value)->whereHas('election', fn ($e) => $e->where('is_active', true)),
            ])
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
                    'sheet_done' => count($s?->sheets ?? []) > 0,
                    'video_done' => (bool) $s?->video_path,
                    'figures_done' => $pu?->results?->isNotEmpty() ?? false,
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
        $active = (array) config('elections.active_offices', ['governor']);
        $asked = AspirantOffice::tryFrom((string) $request->query('office'));

        return ($asked && in_array($asked->value, $active, true)) ? $asked : (AspirantOffice::tryFrom($active[0] ?? '') ?? AspirantOffice::GOVERNOR);
    }

    private function officeOptions(): array
    {
        $active = (array) config('elections.active_offices', ['governor']);

        return collect(AspirantOffice::cases())->filter(fn ($o) => in_array($o->value, $active, true))
            ->map(fn ($o) => ['value' => $o->value, 'label' => ucwords(str_replace('_', ' ', $o->value))])->values()->all();
    }
}
