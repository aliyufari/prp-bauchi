<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\CoordinatorType;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\StoreCoordinatorRequest;
use App\Http\Requests\Coordinator\UpdateCoordinatorRequest;
use App\Http\Resources\CoordinatorResource;
use App\Models\Pu;
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

class CoordinatorController extends Controller
{
    use AppliesJurisdiction;

    private const PROFILE_FIELDS = [
        'phone', 'gender', 'occupation', 'education_status', 'application_id',
        'state_id', 'zone_id', 'lga_id', 'ward_id', 'pu_id',
    ];

    public function index(Request $request)
    {
        Gate::authorize('coordinators:viewAny');
        $this->enforceFilters($request);
        $user = $request->user();

        $query = $this->base($user)->with(['profile.state', 'profile.zone', 'profile.lga', 'profile.ward', 'profile.pu', 'coordinator']);
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $coordinators = $query->paginate($request->integer('per_page', 15))->withQueryString();
        $locations = ScopedLocations::options($user);

        return Inertia::render('dashboard/coordinators/Index', [
            'coordinators' => [
                'data' => CoordinatorResource::collection($coordinators->items()),
                'meta' => [
                    'current_page' => $coordinators->currentPage(),
                    'last_page' => $coordinators->lastPage(),
                    'from' => $coordinators->firstItem(),
                    'to' => $coordinators->lastItem(),
                    'per_page' => $coordinators->perPage(),
                    'total' => $coordinators->total(),
                ],
            ],
            'statistics' => $this->statistics($user),
            'filterOptions' => $locations + [
                'statuses' => [['value' => 'active', 'label' => 'Active'], ['value' => 'inactive', 'label' => 'Inactive']],
                'types' => $this->typeOptions(),
            ],
            'formOptions' => $locations + [
                'pus' => Pu::visibleTo($user)->orderBy('name')->get(['id', 'name']),
                'types' => $this->typeOptions($user), // only levels this user may assign
            ],
            'filters' => $request->only(['search', 'state', 'zone', 'lga', 'ward', 'status', 'type']),
        ]);
    }

    public function store(StoreCoordinatorRequest $request)
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

            $user->assignRole(Role::COORDINATOR->value);
            $user->profile()->create($this->profileData($data));
            $user->coordinator()->create(['type' => $data['type']]);
        });

        return back()->with('success', 'Coordinator created.');
    }

    public function update(UpdateCoordinatorRequest $request, User $coordinator)
    {
        DB::transaction(function () use ($request, $coordinator) {
            $data = $request->validated();

            $coordinator->update([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                ...(!empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
            ]);

            $coordinator->profile()->updateOrCreate(['user_id' => $coordinator->id], $this->profileData($data));
            $coordinator->coordinator()->updateOrCreate(['user_id' => $coordinator->id], ['type' => $data['type']]);
        });

        // Their level or location may have changed; drop any cached jurisdiction.
        Jurisdiction::flush();

        return back()->with('success', 'Coordinator updated.');
    }

    public function show(User $coordinator)
    {
        Gate::authorize('coordinators:view', $coordinator);

        $coordinator->load(['profile.state', 'profile.zone', 'profile.lga', 'profile.ward', 'profile.pu', 'coordinator']);

        return Inertia::render('dashboard/coordinators/Show', ['coordinator' => new CoordinatorResource($coordinator)]);
    }

    public function destroy(User $coordinator)
    {
        Gate::authorize('coordinators:delete', $coordinator);

        $coordinator->delete();

        return back()->with('success', 'Coordinator deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['uuid']]);
        $actor = $request->user();

        $deletable = $this->base($actor)->whereIn('users.id', $request->ids)->get()
            ->filter(fn (User $t) => Gate::forUser($actor)->allows('coordinators:delete', $t));

        $deletable->each->delete();

        $skipped = count($request->ids) - $deletable->count();

        return back()->with('success', "{$deletable->count()} deleted" . ($skipped ? ", {$skipped} skipped (not permitted)." : '.'));
    }

    public function export(Request $request)
    {
        Gate::authorize('coordinators:export');
        $this->enforceFilters($request);

        $query = $this->base($request->user())->with(['profile', 'coordinator']);
        $this->applyFilters($query, $request);

        // return Excel::download(new CoordinatorsExport($query), 'coordinators.xlsx');
    }

    /* ------------------------------------------------------------------ */

    private function base(User $viewer)
    {
        return User::query()->role(Role::COORDINATOR->value)->visibleTo($viewer);
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
            ->when($request->state, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('state_id', $v)))
            ->when($request->zone, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('zone_id', $v)))
            ->when($request->lga, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('lga_id', $v)))
            ->when($request->ward, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('ward_id', $v)))
            ->when($request->status, fn ($q, $v) => $q->where('users.is_active', $v === 'active'))
            ->when($request->type, fn ($q, $v) => $q->whereHas('coordinator', fn ($q) => $q->where('type', $v)));
    }

    protected function applySort($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $direction = $request->get('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'coordinator_type') {
            $query->leftJoin('coordinators', 'coordinators.user_id', '=', 'users.id')
                ->orderBy('coordinators.type', $direction)
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

        // Derive "state level" from the enum rather than hardcoding a case name.
        $stateTypes = collect(CoordinatorType::cases())
            ->filter(fn ($c) => Jurisdiction::levelFor($c->value) === 'state')
            ->map->value->all();

        return [
            'total' => $base()->count(),
            'active' => $base()->where('users.is_active', true)->count(),
            'state_level' => $base()->whereHas('coordinator', fn ($q) => $q->whereIn('type', $stateTypes))->count(),
            'assigned' => $base()->whereHas('profile', fn ($q) => $q->whereNotNull('lga_id'))->count(),
        ];
    }

    /** Pass an actor to list only the levels they are allowed to assign. */
    protected function typeOptions(?User $actor = null): array
    {
        return collect(CoordinatorType::cases())
            ->filter(function ($c) use ($actor) {
                $level = Jurisdiction::levelFor($c->value);

                return !$actor || ($level && RoleHierarchy::canAssign($actor, Role::COORDINATOR->value, $level));
            })
            ->map(fn ($c) => ['value' => $c->value, 'label' => ucwords(str_replace('_', ' ', strtolower($c->value)))])
            ->values()->all();
    }
}