<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AspirantOffice;
use App\Enums\ResultStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Pu;
use App\Models\Result;
use App\Models\User;
use App\Support\AspirantScope;
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

        // Aspirants get the admin dashboard, read-only, limited to the people tied to them.
        if ($this->isAspirant($user)) {
            return Inertia::render('dashboard/Index', [
                'view' => 'admin',
                'data' => $this->adminData($request, $user) + ['readOnly' => true],
            ]);
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
        abort_unless($stateId, 503, config('location.operating_state', 'Bauchi').' State locations have not been seeded.');
        $lgaId = $filters['lga'] ?? null;
        $wardId = $filters['ward'] ?? null;

        abort_unless(!$lgaId || DB::table('lgas')->where('id', $lgaId)
            ->whereIn('zone_id', LocationChain::within('zone', 'state', $stateId))->exists(), 404);
        abort_unless(!$wardId || DB::table('wards')->where('id', $wardId)
            ->whereIn('lga_id', LocationChain::within('lga', 'state', $stateId))
            ->when($lgaId, fn ($q) => $q->where('lga_id', $lgaId))->exists(), 404);

        $totalMembers = $this->count($user, Role::MEMBER, $lgaId, $wardId);
        $partyAgents = $this->count($user, Role::PARTY_AGENT, $lgaId, $wardId);
        $coordinators = $this->count($user, Role::COORDINATOR, $lgaId, $wardId);
        $stakeholders = $this->count($user, Role::STAKEHOLDER, $lgaId, $wardId);
        $lgasCovered = $this->lgasCovered($user, $lgaId, $wardId);
        $totalLgas = $locations['lgas']->count();

        $hasPersistedPeople = ($totalMembers + $partyAgents + $coordinators + $stakeholders) > 0;

        $membershipByLga = $this->membershipByLga($user, $lgaId, $wardId);
        $agentDeployment = $this->agentDeployment($user, $lgaId, $wardId);

        return [
            'stats' => $hasPersistedPeople ? [
                'totalMembers' => $totalMembers,
                'totalMembersGrowth' => $this->growth($user, Role::MEMBER, $lgaId, $wardId),
                'partyAgents' => $partyAgents,
                'partyAgentsGrowth' => $this->growth($user, Role::PARTY_AGENT, $lgaId, $wardId),
                'coordinators' => $coordinators,
                'coordinatorsGrowth' => $this->growth($user, Role::COORDINATOR, $lgaId, $wardId),
                'stakeholders' => $stakeholders,
                'stakeholdersGrowth' => $this->growth($user, Role::STAKEHOLDER, $lgaId, $wardId),
                'upcomingActivities' => 4,
                'lgasCovered' => $lgasCovered,
                'totalLgas' => $totalLgas ?: 20,
            ] : $this->placeholderAdminStats($totalLgas ?: 20),
            'membershipByLga' => !empty($membershipByLga) ? $membershipByLga : $this->placeholderMembershipByLga(),
            'aspirant' => $this->governor($this->isAspirant($user) ? $user : null) ?? $this->placeholderGovernor(),
            'agentDeployment' => $agentDeployment['total'] > 0 ? $agentDeployment : $this->placeholderAgentDeployment(),
            'lgas' => $locations['lgas']->map(fn ($lga) => ['id' => $lga->id, 'name' => $lga->name])->values(),
            'wards' => $locations['wards']->map(fn ($ward) => ['id' => $ward->id, 'name' => $ward->name, 'lga_id' => $ward->lga_id])->values(),
            'selectedLga' => (string) ($lgaId ?? ''),
            'selectedWard' => (string) ($wardId ?? ''),
        ] + $this->emptyWidgets();
    }

    /**
     * Widgets that have no data source yet. Populated with placeholder data
     * until the matching modules are built.
     */
    private function emptyWidgets(): array
    {
        return [
            'campaignActivities' => [
                ['id' => 'act-1', 'title' => 'Bauchi South Town Hall Sensitization', 'date' => '2026-11-20', 'location' => 'Bauchi LGA', 'status' => 'Scheduled'],
                ['id' => 'act-2', 'title' => 'Ward Coordinators Mobilization Summit', 'date' => '2026-11-24', 'location' => 'Katagum LGA', 'status' => 'Scheduled'],
                ['id' => 'act-3', 'title' => 'Polling Unit Agent Training & Accreditation', 'date' => '2026-11-28', 'location' => 'Misau LGA', 'status' => 'Pending'],
            ],
            'upcomingActivities' => [
                ['id' => 'up-1', 'title' => 'Grassroots Voter Registration Drive', 'date' => '2026-11-19', 'time' => '10:00 AM', 'venue' => 'Abubakar Tafawa Balewa Stadium'],
                ['id' => 'up-2', 'title' => 'Youth & Women Wing Strategy Briefing', 'date' => '2026-11-22', 'time' => '02:00 PM', 'venue' => 'State Secretariat, Bauchi'],
            ],
            'electionStrategyActivities' => [
                ['id' => 'str-1', 'phase' => 'Ward Penetration', 'progress' => 78, 'target' => '323 Wards'],
                ['id' => 'str-2', 'phase' => 'PU Agent Deployment', 'progress' => 64, 'target' => '5,450 Polling Units'],
                ['id' => 'str-3', 'phase' => 'Stakeholder Endorsements', 'progress' => 85, 'target' => '20 LGAs'],
            ],
            'lgaSupportData' => [
                ['lga' => 'Bauchi', 'supportPct' => 72, 'registered' => 14200],
                ['lga' => 'Katagum', 'supportPct' => 68, 'registered' => 9850],
                ['lga' => 'Toro', 'supportPct' => 65, 'registered' => 8410],
                ['lga' => 'Misau', 'supportPct' => 70, 'registered' => 7630],
                ['lga' => 'Ningi', 'supportPct' => 61, 'registered' => 6920],
            ],
            'financialSummary' => [
                'totalDonations' => 48500000,
                'donationsGrowth' => 14.2,
                'totalExpenditures' => 31200000,
                'expendituresGrowth' => 8.5,
                'balance' => 17300000,
                'balanceGrowth' => 21.4,
            ],
            'announcements' => [
                ['id' => 'ann-1', 'title' => 'Mandatory PU Agent Verification Deadline', 'date' => '2026-11-15', 'summary' => 'All LGA coordinators must complete biometric and phone verification for polling unit agents before Friday.'],
                ['id' => 'ann-2', 'title' => 'New Ward Mobilization Materials Available', 'date' => '2026-11-12', 'summary' => 'Digital banners, membership forms, and manifestos are ready for download in the portal resources section.'],
            ],
            'recentSupporters' => [
                ['id' => 'sup-1', 'name' => 'Musa Abdullahi', 'lga' => 'Bauchi', 'ward' => 'Makama Sarkin Baki', 'role' => 'Member', 'amount' => 250000, 'contribution' => 250000, 'joinedAt' => '2 hours ago'],
                ['id' => 'sup-2', 'name' => 'Amina Suleiman', 'lga' => 'Katagum', 'ward' => 'Nasarawa Bakin Kasuwa', 'role' => 'Coordinator', 'amount' => 500000, 'contribution' => 500000, 'joinedAt' => '5 hours ago'],
                ['id' => 'sup-3', 'name' => 'Ibrahim Danjuma', 'lga' => 'Toro', 'ward' => 'Toro/Tulay', 'role' => 'Party Agent', 'amount' => 150000, 'contribution' => 150000, 'joinedAt' => '1 day ago'],
                ['id' => 'sup-4', 'name' => 'Zainab Usman', 'lga' => 'Misau', 'ward' => 'Kukadi/Gundari', 'role' => 'Member', 'amount' => 100000, 'contribution' => 100000, 'joinedAt' => '1 day ago'],
            ],
        ];
    }

    private function placeholderAdminStats(int $totalLgas): array
    {
        return [
            'totalMembers' => 42850,
            'totalMembersGrowth' => 12.4,
            'partyAgents' => 4120,
            'partyAgentsGrowth' => 8.7,
            'coordinators' => 343,
            'coordinatorsGrowth' => 5.2,
            'stakeholders' => 185,
            'stakeholdersGrowth' => 9.1,
            'upcomingActivities' => 4,
            'lgasCovered' => min(18, $totalLgas),
            'totalLgas' => $totalLgas,
        ];
    }

    /** @return array<int, array{lga: string, count: int}> */
    private function placeholderMembershipByLga(): array
    {
        return [
            ['lga' => 'Bauchi', 'count' => 8420],
            ['lga' => 'Katagum', 'count' => 5310],
            ['lga' => 'Toro', 'count' => 4690],
            ['lga' => 'Misau', 'count' => 3840],
            ['lga' => 'Ningi', 'count' => 3520],
            ['lga' => 'Jama\'are', 'count' => 2980],
            ['lga' => 'Darazo', 'count' => 2740],
            ['lga' => 'Alkaleri', 'count' => 2510],
        ];
    }

    private function placeholderAgentDeployment(): array
    {
        return [
            'deployed' => 2884,
            'deployedPct' => 70,
            'unassigned' => 742,
            'unassignedPct' => 18,
            'awaitingTraining' => 494,
            'awaitingTrainingPct' => 12,
            'total' => 4120,
        ];
    }

    private function placeholderGovernor(): array
    {
        return [
            'name' => 'Sen. Shehu Buba Umar',
            'title' => 'Governorship Candidate',
            'state' => 'Bauchi',
            'vision' => 'Building an inclusive, prosperous, and secure Bauchi State driven by grassroots empowerment and sustainable infrastructure.',
            'mission' => 'To mobilize every ward and polling unit across all 20 LGAs toward transparent governance and economic revitalization.',
            'imageUrl' => null,
            'overallProgress' => 74,
            'totalSupporters' => 42850,
            'supportersGrowthThisWeek' => 1240,
            'campaignTeamMembers' => 64,
        ];
    }

    private function isAspirant(User $user): bool
    {
        return AspirantScope::is($user);
    }

    private function peopleQuery(User $viewer, Role $role, ?string $lgaId = null, ?string $wardId = null)
    {
        $base = User::role($role->value);
        $base = $this->isAspirant($viewer)
            ? $base->whereIn('users.id', AspirantScope::peopleIds($viewer))
            : $base->visibleTo($viewer);

        return $base
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

        // Three mutually exclusive buckets, so they always add up to the total (a donut needs that):
        // not yet trained / trained and placed at a polling unit / trained but not placed.
        $total = $agents()->count();
        $trained = fn () => $agents()->whereHas('profile', fn ($q) => $q->where('training_status', true));
        $awaiting = $total - $trained()->count();
        $deployed = $trained()->whereHas('profile', fn ($q) => $q->whereNotNull('pu_id'))->count();
        $unassigned = $total - $awaiting - $deployed;
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
    private function governor(?User $of = null): ?array
    {
        $user = User::query()
            ->when($of, fn ($q) => $q->whereKey($of->id))
            ->whereHas('aspirant', fn ($q) => $of ? $q : $q->where('office', AspirantOffice::GOVERNOR->value)->whereHas('constituency', fn ($c) => $c->where('state_id', LocationChain::bauchiStateId())))
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

        $members = $people(Role::MEMBER);
        $partyAgents = $people(Role::PARTY_AGENT);
        $coordinators = $people(Role::COORDINATOR);
        $pollingUnits = Pu::visibleTo($user)->count();
        $registeredVoters = (int) Pu::visibleTo($user)->sum('pus.registered_voters');
        $totalResults = $results()->count();

        $hasPersistedScopedData = ($members + $partyAgents + $coordinators + $pollingUnits + $totalResults) > 0;
        $breakdown = $this->breakdown($j);

        return [
            'scope' => ['level' => $j->level, 'name' => $j->name()],
            'notice' => $j->hasAccess()
                ? null
                : 'No location has been assigned to your account yet. Contact an administrator.',
            'stats' => $hasPersistedScopedData ? [
                'members' => $members,
                'party_agents' => $partyAgents,
                'coordinators' => $coordinators,
                'polling_units' => $pollingUnits,
                'registered_voters' => $registeredVoters,
                'results' => [
                    'total' => $totalResults,
                    'verified' => $results()->where('results.status', ResultStatus::VERIFIED->value)->count(),
                    'pending' => $results()->where('results.status', ResultStatus::PENDING->value)->count(),
                    'disputed' => $results()->where('results.status', ResultStatus::DISPUTED->value)->count(),
                ],
            ] : $this->placeholderScopedStats(),
            'breakdown' => !empty($breakdown['rows']) ? $breakdown : $this->placeholderScopedBreakdown($j),
        ];
    }

    private function placeholderScopedStats(): array
    {
        return [
            'members' => 1450,
            'party_agents' => 128,
            'coordinators' => 16,
            'polling_units' => 142,
            'registered_voters' => 68400,
            'results' => [
                'total' => 96,
                'verified' => 74,
                'pending' => 18,
                'disputed' => 4,
            ],
        ];
    }

    private function placeholderScopedBreakdown(Jurisdiction $j): array
    {
        $child = $j->childLevel() ?? 'ward';

        return [
            'level' => $child,
            'rows' => [
                ['id' => 'placeholder-1', 'name' => 'Central Ward A (Sample)', 'people' => 420, 'results' => 28],
                ['id' => 'placeholder-2', 'name' => 'North Ward B (Sample)', 'people' => 385, 'results' => 24],
                ['id' => 'placeholder-3', 'name' => 'South Ward C (Sample)', 'people' => 340, 'results' => 22],
                ['id' => 'placeholder-4', 'name' => 'East Ward D (Sample)', 'people' => 305, 'results' => 22],
            ],
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
            ])->values()->all(),
        ];
    }
}