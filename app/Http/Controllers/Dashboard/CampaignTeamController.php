<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\CampaignTeam\StoreCampaignTeamRequest;
use App\Http\Requests\CampaignTeam\UpdateCampaignTeamRequest;
use App\Http\Resources\CampaignTeamMemberResource;
use App\Http\Resources\CampaignTeamResource;
use App\Models\Aspirant;
use App\Models\CampaignTeam;
use App\Models\CampaignTeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CampaignTeamController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', CampaignTeam::class);
        $user = $request->user();

        $query = CampaignTeam::query()->visibleTo($user)->with(['aspirant.user', 'teamLead'])->withCount('members');
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $teams = $query->paginate($request->integer('per_page', 15))->withQueryString();
        $aspirants = $this->aspirantOptions($user);

        return Inertia::render('dashboard/campaign-teams/Index', [
            'teams' => [
                'data' => CampaignTeamResource::collection($teams->items()),
                'meta' => [
                    'current_page' => $teams->currentPage(), 'last_page' => $teams->lastPage(),
                    'from' => $teams->firstItem(), 'to' => $teams->lastItem(),
                    'per_page' => $teams->perPage(), 'total' => $teams->total(),
                ],
            ],
            'statistics' => $this->statistics($user),
            'filterOptions' => [
                'aspirants' => $aspirants,
                'statuses' => [['value' => 'active', 'label' => 'Active'], ['value' => 'inactive', 'label' => 'Inactive']],
            ],
            'formOptions' => ['aspirants' => $aspirants, 'potentialLeads' => $this->potentialLeads($user)],
            'filters' => $request->only(['search', 'aspirant', 'status']),
        ]);
    }

    public function store(StoreCampaignTeamRequest $request)
    {
        CampaignTeam::create($request->validated());

        return back()->with('success', 'Campaign team created.');
    }

    public function update(UpdateCampaignTeamRequest $request, CampaignTeam $campaign_team)
    {
        $campaign_team->update($request->validated());

        return back()->with('success', 'Campaign team updated.');
    }

    public function destroy(CampaignTeam $campaign_team)
    {
        Gate::authorize('delete', $campaign_team);

        $campaign_team->delete();

        return back()->with('success', 'Campaign team deleted.');
    }

    public function show(CampaignTeam $campaign_team)
    {
        Gate::authorize('view', $campaign_team);

        $campaign_team->load(['aspirant.user', 'teamLead']);
        $members = $campaign_team->members()->with('user.profile')->latest()->get();

        return Inertia::render('dashboard/campaign-teams/Show', [
            'team' => new CampaignTeamResource($campaign_team),
            'members' => CampaignTeamMemberResource::collection($members),
        ]);
    }

    protected function applyFilters($query, Request $request): void
    {
        $query
            ->when($request->search, fn ($q, $s) => $q->where('campaign_teams.name', 'like', "%{$s}%"))
            ->when($request->aspirant, fn ($q, $v) => $q->where('campaign_teams.aspirant_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('campaign_teams.is_active', $v === 'active'));
    }

    protected function applySort($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $direction = $request->get('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy('campaign_teams.' . (in_array($sortBy, ['name', 'created_at'], true) ? $sortBy : 'created_at'), $direction);
    }

    protected function statistics(User $user): array
    {
        $teams = fn () => CampaignTeam::query()->visibleTo($user);

        return [
            'total' => $teams()->count(),
            'active' => $teams()->where('campaign_teams.is_active', true)->count(),
            'total_members' => CampaignTeamMember::whereIn('campaign_team_id', $teams()->select('campaign_teams.id'))->count(),
            'unassigned_lead' => $teams()->whereNull('campaign_teams.team_lead_id')->count(),
        ];
    }

    /** Managers see every campaign; an aspirant sees only their own. */
    protected function aspirantOptions(User $user): array
    {
        return Aspirant::with('user')
            ->when(!$user->checkPermissionTo('campaign-teams.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->get()
            ->map(fn (Aspirant $a) => [
                'id' => $a->id,
                'name' => $a->user ? trim("{$a->user->first_name} {$a->user->last_name}") : 'Unnamed',
                'office' => $a->office ? ucwords(str_replace('_', ' ', strtolower($a->office->value))) : null,
            ])->values()->all();
    }

    /** Only campaign accounts, never the whole user table. */
    protected function potentialLeads(User $user): array
    {
        return User::role(Role::CAMPAIGN_TEAM_MEMBER->value)
            ->when(!$user->checkPermissionTo('campaign-teams.manage'), fn ($q) => $q
                ->whereHas('campaignTeamMembership.team.aspirant', fn ($a) => $a->where('user_id', $user->id)))
            ->orderBy('first_name')->get(['id', 'first_name', 'last_name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => trim("{$u->first_name} {$u->last_name}")])
            ->values()->all();
    }
}