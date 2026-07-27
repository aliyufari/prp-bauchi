<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\CampaignTeam\StoreCampaignTeamRequest;
use App\Http\Requests\CampaignTeam\UpdateCampaignTeamRequest;
use App\Http\Resources\CampaignTeamResource;
use App\Models\Aspirant;
use App\Models\CampaignTeam;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CampaignTeamController extends Controller
{
    public function index(Request $request)
    {
        $query = CampaignTeam::query()
            ->with(['aspirant.user', 'teamLead'])
            ->withCount('members');

        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $teams = $query->paginate($request->integer('per_page', 15))->withQueryString();

        return Inertia::render('dashboard/campaign-teams/Index', [
            'teams' => [
                'data' => CampaignTeamResource::collection($teams->items()),
                'meta' => [
                    'current_page' => $teams->currentPage(),
                    'last_page' => $teams->lastPage(),
                    'from' => $teams->firstItem(),
                    'to' => $teams->lastItem(),
                    'per_page' => $teams->perPage(),
                    'total' => $teams->total(),
                ],
            ],
            'statistics' => $this->statistics(),
            'filterOptions' => [
                'aspirants' => $this->aspirantOptions(),
                'statuses' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                ],
            ],
            'formOptions' => [
                'aspirants' => $this->aspirantOptions(),
                'potentialLeads' => User::orderBy('first_name')->get(['id', 'first_name', 'last_name'])
                    ->map(fn ($u) => ['id' => $u->id, 'name' => trim("{$u->first_name} {$u->last_name}")])
                    ->values(),
            ],
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
        $campaign_team->delete();

        return back()->with('success', 'Campaign team deleted.');
    }

    /**
     * Single-team page: profile + roster management.
     * Authorization: super_admin/admin always allowed; otherwise the
     * visiting user must be this team's assigned lead.
     */
    public function show(Request $request, CampaignTeam $campaign_team)
    {
        abort_unless($campaign_team->canBeManagedBy($request->user()), 403);

        $campaign_team->load(['aspirant.user', 'teamLead']);
        $members = $campaign_team->members()->with('user.profile')->latest()->get();

        return Inertia::render('dashboard/campaign-teams/Show', [
            'team' => new CampaignTeamResource($campaign_team),
            'members' => \App\Http\Resources\CampaignTeamMemberResource::collection($members),
        ]);
    }

    protected function applyFilters($query, Request $request): void
    {
        $query
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($request->aspirant, fn ($q, $v) => $q->where('aspirant_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('is_active', $v === 'active'));
    }

    protected function applySort($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $direction = $request->get('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $sortable = ['name', 'created_at'];
        $query->orderBy(in_array($sortBy, $sortable, true) ? $sortBy : 'created_at', $direction);
    }

    protected function statistics(): array
    {
        return [
            'total' => CampaignTeam::count(),
            'active' => CampaignTeam::where('is_active', true)->count(),
            'total_members' => \App\Models\CampaignTeamMember::count(),
            'unassigned_lead' => CampaignTeam::whereNull('team_lead_id')->count(),
        ];
    }

    protected function aspirantOptions(): array
    {
        return Aspirant::with('user')->get()
            ->map(fn (Aspirant $a) => [
                'id' => $a->id,
                'name' => $a->user ? trim("{$a->user->first_name} {$a->user->last_name}") : 'Unnamed',
                'office' => $a->office ? ucwords(str_replace('_', ' ', strtolower($a->office->value))) : null,
            ])->values()->all();
    }
}