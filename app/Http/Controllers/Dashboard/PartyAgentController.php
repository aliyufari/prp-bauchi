<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\PartyAgentType;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\PartyAgent\StorePartyAgentRequest;
use App\Http\Requests\PartyAgent\UpdatePartyAgentRequest;
use App\Http\Resources\PartyAgentResource;
use App\Models\User;
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

class PartyAgentController extends Controller
{
    use AppliesJurisdiction, StoresUserAvatar;

    private const PROFILE_FIELDS = [
        'phone', 'gender', 'occupation', 'education_status', 'application_id',
        'state_id', 'zone_id', 'lga_id', 'ward_id', 'pu_id',
    ];

    public function index(Request $request)
    {
        Gate::authorize('party-agents:viewAny');
        $this->enforceFilters($request);
        $user = $request->user();

        $query = $this->base($user)->with(['profile.state', 'profile.zone', 'profile.lga', 'profile.ward', 'profile.pu', 'partyAgent']);
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $agents = $query->paginate($request->integer('per_page', 15))->withQueryString();
        $locations = ScopedLocations::options($user);

        return Inertia::render('dashboard/party-agents/Index', [
            'agents' => [
                'data' => PartyAgentResource::collection($agents->items()),
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
            'filterOptions' => ['wards' => $locations['wards'] ?? collect()] + [
                'statuses' => [['value' => 'active', 'label' => 'Active'], ['value' => 'inactive', 'label' => 'Inactive']],
                'types' => $this->typeOptions(),
            ],
            'formOptions' => [
                'types' => $this->typeOptions(),
            ],
            'filters' => $request->only(['search', 'ward', 'pu', 'status', 'type']),
        ]);
    }

    public function store(StorePartyAgentRequest $request)
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

            $user->assignRole(Role::PARTY_AGENT->value);
            $user->profile()->create($this->profileData($data)); // observer completes the location chain
            $user->partyAgent()->create(['type' => $data['type']]);
            $this->syncUserAvatar($user, $request);
        });

        return back()->with('success', 'Party agent created.');
    }

    public function update(UpdatePartyAgentRequest $request, User $party_agent)
    {
        DB::transaction(function () use ($request, $party_agent) {
            $data = $request->validated();

            $party_agent->update([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                ...(!empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
            ]);

            $party_agent->profile()->updateOrCreate(['user_id' => $party_agent->id], $this->profileData($data));
            $party_agent->partyAgent()->updateOrCreate(['user_id' => $party_agent->id], ['type' => $data['type']]);
            $this->syncUserAvatar($party_agent, $request);
        });

        return back()->with('success', 'Party agent updated.');
    }

    public function show(User $party_agent)
    {
        Gate::authorize('party-agents:view', $party_agent);

        $party_agent->load(['profile.state', 'profile.zone', 'profile.lga', 'profile.ward', 'profile.pu', 'partyAgent']);

        return Inertia::render('dashboard/people/Show', [
            'person' => new PartyAgentResource($party_agent),
            'kind' => 'Party Agent',
        ]);
    }

    public function destroy(User $party_agent)
    {
        Gate::authorize('party-agents:delete', $party_agent);

        $party_agent->delete();

        return back()->with('success', 'Party agent deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['uuid']]);
        $actor = $request->user();

        // Only in-scope rows are loaded; each is then policy-checked.
        $deletable = $this->base($actor)->whereIn('users.id', $request->ids)->get()
            ->filter(fn (User $t) => Gate::forUser($actor)->allows('party-agents:delete', $t));

        $deletable->each->delete();

        $skipped = count($request->ids) - $deletable->count();

        return back()->with('success', "{$deletable->count()} deleted" . ($skipped ? ", {$skipped} skipped (not permitted)." : '.'));
    }

    public function export(Request $request)
    {
        Gate::authorize('party-agents:export');
        $this->enforceFilters($request);

        $query = $this->base($request->user())->with(['profile.lga', 'profile.ward', 'profile.pu', 'partyAgent']);
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);
        $rows = ExportRows::people($query->get(), 'partyAgent', ['type']);

        return Excel::download(new FormattedArrayExport(['Full name', 'Email', 'Phone', 'Gender', 'Occupation', 'Education level', 'LGA', 'Ward', 'Polling unit', 'Status', 'Type'], $rows, 'Party Agents'), 'party-agents.xlsx');
    }

    /* ------------------------------------------------------------------ */

    private function base(User $viewer)
    {
        return User::query()->role(Role::PARTY_AGENT->value)->visibleTo($viewer);
    }

    private function profileData(array $data): array
    {
        return array_intersect_key($data, array_flip(self::PROFILE_FIELDS));
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
            ->when($request->ward, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('ward_id', $v)))
            ->when($request->pu, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('pu_id', $v)))
            ->when($request->status, fn ($q, $v) => $q->where('users.is_active', $v === 'active'))
            ->when($request->type, fn ($q, $v) => $q->whereHas('partyAgent', fn ($q) => $q->where('type', $v)));
    }

    protected function applySort($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $direction = $request->get('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'agent_type') {
            $query->leftJoin('party_agents', 'party_agents.user_id', '=', 'users.id')
                ->orderBy('party_agents.type', $direction)
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
        $base = fn () => $this->base($viewer);

        return [
            'total' => $base()->count(),
            'active' => $base()->where('users.is_active', true)->count(),
            'deployed' => $base()->whereHas('profile', fn ($q) => $q->whereNotNull('pu_id'))->count(),
            'unassigned' => $base()->whereDoesntHave('profile', fn ($q) => $q->whereNotNull('pu_id'))->count(),
        ];
    }

    protected function typeOptions(): array
    {
        return collect(PartyAgentType::cases())
            ->map(fn ($c) => ['value' => $c->value, 'label' => ucwords(str_replace('_', ' ', strtolower($c->value)))])
            ->values()->all();
    }
}