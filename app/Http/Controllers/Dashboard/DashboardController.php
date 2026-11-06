<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AspirantOffice;
use App\Enums\ResultStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Pu;
use App\Models\Result;
use App\Models\User;
use App\Support\Jurisdiction;
use App\Support\LocationChain;
use App\Support\ScopedLocations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if (!$user->checkPermissionTo('dashboard.view')) {
            abort_unless(
                $user->checkPermissionTo('portal.access'),
                403,
                'Your account has no access assigned yet. Contact an administrator.',
            );

            return redirect()->route('portal.home');
        }

        $j = Jurisdiction::for($user);

        return Inertia::render('dashboard/Index', [
            'view' => $j->unrestricted ? 'admin' : ($j->level ?? 'ward'),
            'data' => $j->unrestricted
                ? $this->adminData($request, $user)
                : $this->scopedData($user, $j),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Admin / super admin: the full dashboard                             */
    /* ------------------------------------------------------------------ */

    private function adminData(Request $request, User $user): array
    {
        $locations = ScopedLocations::options($user);
        $filters = $request->validate(['lga' => ['nullable', 'uuid'], 'ward' => ['nullable', 'uuid']]);
        $stateId = LocationChain::bauchiStateId();
        abort_unless($stateId, 503, 'Bauchi State locations have not been seeded.');
        $lgaId = $filters['lga'] ?? null;
        $wardId = $filters['ward'] ?? null;

        abort_unless(!$lgaId || DB::table('lgas')->where('id', $lgaId)
            ->whereIn('zone_id', LocationChain::within('zone', 'state', $stateId))->exists(), 404);
        abort_unless(!$wardId || DB::table('wards')->where('id', $wardId)
            ->whereIn('lga_id', LocationChain::within('lga', 'state', $stateId))
            ->when($lgaId, fn ($q) => $q->where('lga_id', $lgaId))->exists(), 404);

        return [
            'stats' => [
                'totalMembers' => $this->count($user, Role::MEMBER, $lgaId, $wardId),
                'totalMembersGrowth' => $this->growth($user, Role::MEMBER, $lgaId, $wardId),
                'partyAgents' => $this->count($user, Role::PARTY_AGENT, $lgaId, $wardId),
                'partyAgentsGrowth' => $this->growth($user, Role::PARTY_AGENT, $lgaId, $wardId),
                'coordinators' => $this->count($user, Role::COORDINATOR, $lgaId, $wardId),
                'coordinatorsGrowth' => $this->growth($user, Role::COORDINATOR, $lgaId, $wardId),
                'stakeholders' => $this->count($user, Role::STAKEHOLDER, $lgaId, $wardId),
                'stakeholdersGrowth' => $this->growth($user, Role::STAKEHOLDER, $lgaId, $wardId),
                'upcomingActivities' => 0, // no events module yet
                'lgasCovered' => $this->lgasCovered($user, $lgaId, $wardId),
                'totalLgas' => $locations['lgas']->count(),
            ],
            'membershipByLga' => $this->membershipByLga($user, $lgaId, $wardId),
            'aspirant' => $this->governor(),
            'agentDeployment' => $this->agentDeployment($user, $lgaId, $wardId),
            'lgas' => $locations['lgas']->map(fn ($lga) => ['id' => $lga->id, 'name' => $lga->name])->values(),
            'wards' => $locations['wards']->map(fn ($ward) => ['id' => $ward->id, 'name' => $ward->name, 'lga_id' => $ward->lga_id])->values(),
            'selectedLga' => (string) ($lgaId ?? ''),
            'selectedWard' => (string) ($wardId ?? ''),
        ] + $this->emptyWidgets();
    }

    /**
     * Widgets that have no data source yet. Paste your old mock arrays here
     * if you want the cards populated until the matching modules are built.
     */
    private function emptyWidgets(): array
    {
        return [
            'campaignActivities' => [],
            'upcomingActivities' => [],
            'electionStrategyActivities' => [],
            'lgaSupportData' => [],
            'financialSummary' => [
                'totalDonations' => 0, 'donationsGrowth' => 0,
                'totalExpenditures' => 0, 'expendituresGrowth' => 0,
                'balance' => 0, 'balanceGrowth' => 0,
            ],
            'announcements' => [],
            'recentSupporters' => [],
        ];
    }

    private function peopleQuery(User $viewer, Role $role, ?string $lgaId = null, ?string $wardId = null)
    {
        return User::role($role->value)->visibleTo($viewer)
            ->when($lgaId, fn ($q) => $q->whereHas('profile', fn ($p) => $p->where('lga_id', $lgaId)))
            ->when($wardId, fn ($q) => $q->whereHas('profile', fn ($p) => $p->where('ward_id', $wardId)));
    }

    private function count(User $viewer, Role $role, ?string $lgaId = null, ?string $wardId = null): int
    {
        return $this->peopleQuery($viewer, $role, $lgaId, $wardId)->count();
    }

    /** Percent change in sign-ups: last 30 days vs the 30 days before. */
    private function growth(User $viewer, Role $role, ?string $lgaId = null, ?string $wardId = null): float
    {
        $between = fn ($from, $to) => $this->peopleQuery($viewer, $role, $lgaId, $wardId)
            ->whereBetween('users.created_at', [$from, $to])->count();

        $current = $between(now()->subDays(30), now());
        $previous = $between(now()->subDays(60), now()->subDays(30));

        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function lgasCovered(User $viewer, ?string $lgaId = null, ?string $wardId = null): int
    {
        return DB::table('user_profiles')
            ->whereIn('user_id', $this->peopleQuery($viewer, Role::MEMBER, $lgaId, $wardId)->select('users.id'))
            ->whereNotNull('lga_id')
            ->distinct()
            ->count('lga_id');
    }

    /** @return array<int, array{lga: string, count: int}> */
    private function membershipByLga(User $viewer, ?string $lgaId = null, ?string $wardId = null): array
    {
        return DB::table('user_profiles as p')
            ->join('lgas as l', 'l.id', '=', 'p.lga_id')
            ->whereIn('p.user_id', $this->peopleQuery($viewer, Role::MEMBER, $lgaId, $wardId)->select('users.id'))
            ->when($lgaId, fn ($q) => $q->where('p.lga_id', $lgaId))
            ->when($wardId, fn ($q) => $q->where('p.ward_id', $wardId))
            ->groupBy('l.id', 'l.name')
            ->selectRaw('l.name as lga, count(*) as count')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->map(fn ($r) => ['lga' => $r->lga, 'count' => (int) $r->count])
            ->all();
    }

    private function agentDeployment(User $viewer, ?string $lgaId = null, ?string $wardId = null): array
    {
        $agents = fn () => $this->peopleQuery($viewer, Role::PARTY_AGENT, $lgaId, $wardId);

        $total = $agents()->count();
        $deployed = $agents()->whereHas('profile', fn ($q) => $q->whereNotNull('pu_id'))->count();
        $awaiting = $agents()->whereHas('profile', fn ($q) => $q->where('training_status', false))->count();
        $unassigned = $total - $deployed;
        $pct = fn (int $n) => $total ? (int) round(($n / $total) * 100) : 0;

        return [
            'deployed' => $deployed,
            'deployedPct' => $pct($deployed),
            'unassigned' => $unassigned,
            'unassignedPct' => $pct($unassigned),
            'awaitingTraining' => $awaiting,
            'awaitingTrainingPct' => $pct($awaiting),
            'total' => $total,
        ];
    }

    /** The party's governorship candidate, or null when none is set up yet. */
    private function governor(): ?array
    {
        $user = User::query()
            ->whereHas('aspirant', fn ($q) => $q->where('office', AspirantOffice::GOVERNOR->value)->whereHas('constituency', fn ($c) => $c->where('state_id', LocationChain::bauchiStateId())))
            ->with(['profile.state', 'aspirant'])
            ->first();

        if (!$user) {
            return null;
        }

        $a = $user->aspirant;

        return [
            'name' => trim("{$user->first_name} {$user->last_name}"),
            'title' => $a->title ?? 'Governorship Candidate',
            'state' => $user->profile?->state?->name,
            'vision' => $a->vision,
            'mission' => $a->mission,
            'imageUrl' => $a->picture ?? $a->avatar ?? $user->avatar_url, // null -> have the card fall back to your bundled governor image
            'overallProgress' => $a->overall_progress,
            'totalSupporters' => $a->total_supporters,
            'supportersGrowthThisWeek' => $a->supporters_growth_this_week,
            'campaignTeamMembers' => $a->campaignTeams()->count(),
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Coordinators, supervisors, returning officers: scoped view          */
    /* ------------------------------------------------------------------ */

    private function scopedData(User $user, Jurisdiction $j): array
    {
        $people = fn (Role $role) => $this->count($user, $role);
        $results = fn () => Result::query()->visibleTo($user);

        return [
            'scope' => ['level' => $j->level, 'name' => $j->name()],
            'notice' => $j->hasAccess()
                ? null
                : 'No location has been assigned to your account yet. Contact an administrator.',
            'stats' => [
                'members' => $people(Role::MEMBER),
                'party_agents' => $people(Role::PARTY_AGENT),
                'coordinators' => $people(Role::COORDINATOR),
                'polling_units' => Pu::visibleTo($user)->count(),
                'registered_voters' => (int) Pu::visibleTo($user)->sum('pus.registered_voters'),
                'results' => [
                    'total' => $results()->count(),
                    'verified' => $results()->where('results.status', ResultStatus::VERIFIED->value)->count(),
                    'pending' => $results()->where('results.status', ResultStatus::PENDING->value)->count(),
                    'disputed' => $results()->where('results.status', ResultStatus::DISPUTED->value)->count(),
                ],
            ],
            'breakdown' => $this->breakdown($j),
        ];
    }

    /** One row per child of the user's level (state sees zones, zone sees LGAs, ...). */
    private function breakdown(Jurisdiction $j): array
    {
        $child = $j->childLevel();

        if (!$child || !$j->hasAccess()) {
            return ['level' => $child, 'rows' => []];
        }

        $table = LocationChain::TABLES[$child];

        $rows = $j->constrainTable(
            $child,
            DB::table($table)->select("{$table}.id", "{$table}.name")->orderBy("{$table}.name"),
        )->limit(200)->get();

        $ids = $rows->pluck('id');
        $col = "{$child}_id";

        $countBy = fn (string $from) => DB::table($from)
            ->whereIn($col, $ids)
            ->groupBy($col)
            ->selectRaw("{$col} as k, count(*) as c")
            ->pluck('c', 'k');

        $people = $countBy('user_profiles');
        $results = $countBy('results');

        return [
            'level' => $child,
            'rows' => $rows->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'people' => (int) ($people[$r->id] ?? 0),
                'results' => (int) ($results[$r->id] ?? 0),
            ])->values(),
        ];
    }
}