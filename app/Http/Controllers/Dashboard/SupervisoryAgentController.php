<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Role;
use App\Enums\SupervisoryAgentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupervisoryAgent\StoreSupervisoryAgentRequest;
use App\Http\Requests\SupervisoryAgent\UpdateSupervisoryAgentRequest;
use App\Http\Resources\SupervisoryAgentResource;
use App\Models\SupervisoryAgent;
use App\Models\User;
use App\Support\Jurisdiction;
use App\Support\RoleHierarchy;
use App\Support\ScopedLocations;
use App\Traits\AppliesJurisdiction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Traits\StoresUserAvatar;
use App\Exports\FormattedArrayExport;
use App\Support\ExportRows;
use Maatwebsite\Excel\Facades\Excel;

class SupervisoryAgentController extends Controller
{
    use AppliesJurisdiction, StoresUserAvatar;

    private const PROFILE_FIELDS = [
        'phone', 'gender', 'occupation', 'education_status', 'application_id',
        'state_id', 'zone_id', 'lga_id', 'ward_id', 'pu_id',
    ];

    public function index(Request $request)
    {
        Gate::authorize('supervisory-agents:viewAny');
        $this->enforceFilters($request);
        $user = $request->user();

        $query = $this->base($user)->with(['profile.state', 'profile.zone', 'profile.lga', 'profile.ward', 'profile.pu', 'supervisoryAgent']);
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $agents = $query->paginate($request->integer('per_page', 15))->withQueryString();
        $locations = ScopedLocations::options($user);

        return Inertia::render('dashboard/supervisory-agents/Index', [
            'agents' => [
                'data' => SupervisoryAgentResource::collection($agents->items()),
                'meta' => [
                    'current_page' => $agents->currentPage(),
                    'last_page' => $agents->lastPage(),
                    'from' => $agents->firstItem(),
                    'to' => $agents->lastItem(),
                    'per_page' => $agents->perPage(),
                    'total' => $agents->total(),
                ],
            ],
            'statistics' => $this->statistics($user),
            'filterOptions' => $locations + [
                'statuses' => [['value' => 'active', 'label' => 'Active'], ['value' => 'inactive', 'label' => 'Inactive']],
                'types' => $this->typeOptions(),
            ],
            'formOptions' => [
                'types' => $this->typeOptions($user), // only levels this user may assign
            ],
            'filters' => $request->only(['search', 'state', 'zone', 'lga', 'ward', 'pu', 'status', 'type']),
        ]);
    }

    public function store(StoreSupervisoryAgentRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();

            $user = User::create([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $user->assignRole(Role::SUPERVISORY_AGENT->value);
            $user->profile()->create($this->profileData($data));
            $user->supervisoryAgent()->create($this->agentData($data));
            $this->syncUserAvatar($user, $request);
        });

        return back()->with('success', 'Supervisory agent created.');
    }

    public function update(UpdateSupervisoryAgentRequest $request, User $supervisory_agent)
    {
        DB::transaction(function () use ($request, $supervisory_agent) {
            $data = $request->validated();

            $supervisory_agent->update([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                ...(!empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
            ]);

            $supervisory_agent->profile()->updateOrCreate(['user_id' => $supervisory_agent->id], $this->profileData($data));
            $supervisory_agent->supervisoryAgent()->updateOrCreate(['user_id' => $supervisory_agent->id], $this->agentData($data));
            $this->syncUserAvatar($supervisory_agent, $request);
        });

        Jurisdiction::flush();

        return back()->with('success', 'Supervisory agent updated.');
    }

    public function show(User $supervisory_agent)
    {
        Gate::authorize('supervisory-agents:view', $supervisory_agent);

        $supervisory_agent->load(['profile.state', 'profile.zone', 'profile.lga', 'profile.ward', 'profile.pu', 'supervisoryAgent']);

        return Inertia::render('dashboard/people/Show', [
            'person' => new SupervisoryAgentResource($supervisory_agent),
            'kind' => 'Supervisory Agent',
        ]);
    }

    public function destroy(User $supervisory_agent)
    {
        Gate::authorize('supervisory-agents:delete', $supervisory_agent);

        $supervisory_agent->delete();

        return back()->with('success', 'Supervisory agent deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['uuid']]);
        $actor = $request->user();

        $deletable = $this->base($actor)->whereIn('users.id', $request->ids)->get()
            ->filter(fn (User $t) => Gate::forUser($actor)->allows('supervisory-agents:delete', $t));

        $deletable->each->delete();

        $skipped = count($request->ids) - $deletable->count();

        return back()->with('success', "{$deletable->count()} deleted" . ($skipped ? ", {$skipped} skipped (not permitted)." : '.'));
    }

    public function export(Request $request)
    {
        Gate::authorize('supervisory-agents:export');
        $this->enforceFilters($request);

        $query = $this->base($request->user())->with(['profile.lga', 'profile.ward', 'profile.pu', 'supervisoryAgent']);
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);
        $rows = ExportRows::people($query->get(), 'supervisoryAgent', ['type', 'agents_supervised']);

        return Excel::download(new FormattedArrayExport(['Full name', 'Email', 'Phone', 'Gender', 'Occupation', 'Education level', 'LGA', 'Ward', 'Polling unit', 'Status', 'Type', 'Agents Supervised'], $rows, 'Supervisory Agents'), 'supervisory-agents.xlsx');
    }

    /* ------------------------------------------------------------------ */

    private function base(User $viewer)
    {
        return User::query()->role(Role::SUPERVISORY_AGENT->value)->visibleTo($viewer);
    }

    private function profileData(array $data): array
    {
        return array_intersect_key($data, array_flip(self::PROFILE_FIELDS));
    }

    private function agentData(array $data): array
    {
        return ['type' => $data['type'], 'agents_supervised' => $data['agents_supervised'] ?? 0];
    }

    protected function applyFilters($query, Request $request): void
    {
        $query
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('users.first_name', 'like', "%{$s}%")
                ->orWhere('users.last_name', 'like', "%{$s}%")
                ->orWhere('users.email', 'like', "%{$s}%")
                ->orWhereHas('profile', fn ($q) => $q
                    ->where('application_id', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%"))))
            ->when($request->state, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('state_id', $v)))
            ->when($request->zone, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('zone_id', $v)))
            ->when($request->lga, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('lga_id', $v)))
            ->when($request->ward, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('ward_id', $v)))
            ->when($request->pu, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('pu_id', $v)))
            ->when($request->status, fn ($q, $v) => $q->where('users.is_active', $v === 'active'))
            ->when($request->type, fn ($q, $v) => $q->whereHas('supervisoryAgent', fn ($q) => $q->where('type', $v)));
    }

    protected function applySort($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $direction = $request->get('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['supervisor_type', 'agents_supervised'], true)) {
            $column = $sortBy === 'supervisor_type' ? 'type' : 'agents_supervised';

            $query->leftJoin('supervisory_agents', 'supervisory_agents.user_id', '=', 'users.id')
                ->orderBy("supervisory_agents.{$column}", $direction)
                ->select('users.*');

            return;
        }

        if ($sortBy === 'application_id') {
            $query->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
                ->orderBy('user_profiles.application_id', $direction)
                ->select('users.*');

            return;
        }

        $sortable = ['first_name', 'last_name', 'email', 'is_active', 'created_at'];
        $query->orderBy('users.' . (in_array($sortBy, $sortable, true) ? $sortBy : 'created_at'), $direction);
    }

    protected function statistics(User $viewer): array
    {
        $ids = fn () => $this->base($viewer)->select('users.id');

        return [
            'total' => $this->base($viewer)->count(),
            'active' => $this->base($viewer)->where('users.is_active', true)->count(),
            'total_agents_supervised' => (int) SupervisoryAgent::whereIn('user_id', $ids())->sum('agents_supervised'),
            // The location observer fills state_id whenever any location is set,
            // so a null state_id means "no location assigned at all".
            'unassigned' => $this->base($viewer)->whereDoesntHave('profile', fn ($q) => $q->whereNotNull('state_id'))->count(),
        ];
    }

    /** Pass an actor to list only the levels they are allowed to assign. */
    protected function typeOptions(?User $actor = null): array
    {
        return collect(SupervisoryAgentType::cases())
            ->filter(function ($c) use ($actor) {
                $level = Jurisdiction::levelFor($c->value);

                return !$actor || ($level && RoleHierarchy::canAssign($actor, Role::SUPERVISORY_AGENT->value, $level));
            })
            ->map(fn ($c) => ['value' => $c->value, 'label' => ucwords(str_replace('_', ' ', strtolower($c->value)))])
            ->values()->all();
    }
}