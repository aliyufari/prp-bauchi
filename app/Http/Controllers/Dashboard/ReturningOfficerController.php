<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReturningOfficer\StoreReturningOfficerRequest;
use App\Http\Requests\ReturningOfficer\UpdateReturningOfficerRequest;
use App\Http\Resources\ReturningOfficerResource;
use App\Models\Pu;
use App\Models\ReturningOfficer;
use App\Models\User;
use App\Support\ScopedLocations;
use App\Traits\AppliesJurisdiction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ReturningOfficerController extends Controller
{
    use AppliesJurisdiction;

    private const PROFILE_FIELDS = [
        'phone', 'gender', 'occupation', 'education_status', 'application_id',
        'state_id', 'zone_id', 'lga_id', 'ward_id', 'pu_id',
    ];

    public function index(Request $request)
    {
        Gate::authorize('returning-officers:viewAny');
        $this->enforceFilters($request);
        $user = $request->user();

        $query = $this->base($user)->with(['profile.state', 'profile.zone', 'profile.lga', 'profile.ward', 'profile.pu', 'returningOfficer']);
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $officers = $query->paginate($request->integer('per_page', 15))->withQueryString();
        $locations = ScopedLocations::options($user);

        return Inertia::render('dashboard/returning-officers/Index', [
            'officers' => [
                'data' => ReturningOfficerResource::collection($officers->items()),
                'meta' => [
                    'current_page' => $officers->currentPage(),
                    'last_page' => $officers->lastPage(),
                    'from' => $officers->firstItem(),
                    'to' => $officers->lastItem(),
                    'per_page' => $officers->perPage(),
                    'total' => $officers->total(),
                ],
            ],
            'statistics' => $this->statistics($user),
            'filterOptions' => $locations + [
                'statuses' => [['value' => 'active', 'label' => 'Active'], ['value' => 'inactive', 'label' => 'Inactive']],
            ],
            'formOptions' => $locations + [
                'pus' => Pu::visibleTo($user)->orderBy('name')->get(['id', 'name']),
            ],
            'filters' => $request->only(['search', 'state', 'zone', 'lga', 'ward', 'status']),
        ]);
    }

    public function store(StoreReturningOfficerRequest $request)
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

            $user->assignRole(Role::RETURNING_OFFICER->value);
            $user->profile()->create($this->profileData($data));
            $user->returningOfficer()->create($this->officerData($data));
        });

        return back()->with('success', 'Returning officer created.');
    }

    public function update(UpdateReturningOfficerRequest $request, User $returning_officer)
    {
        DB::transaction(function () use ($request, $returning_officer) {
            $data = $request->validated();

            $returning_officer->update([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                ...(!empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
            ]);

            $returning_officer->profile()->updateOrCreate(['user_id' => $returning_officer->id], $this->profileData($data));
            $returning_officer->returningOfficer()->updateOrCreate(['user_id' => $returning_officer->id], $this->officerData($data));
        });

        return back()->with('success', 'Returning officer updated.');
    }

    public function show(User $returning_officer)
    {
        Gate::authorize('returning-officers:view', $returning_officer);

        $returning_officer->load(['profile.state', 'profile.zone', 'profile.lga', 'profile.ward', 'profile.pu', 'returningOfficer']);

        return Inertia::render('dashboard/returning-officers/Show', ['officer' => new ReturningOfficerResource($returning_officer)]);
    }

    public function destroy(User $returning_officer)
    {
        Gate::authorize('returning-officers:delete', $returning_officer);

        $returning_officer->delete();

        return back()->with('success', 'Returning officer deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['uuid']]);
        $actor = $request->user();

        $deletable = $this->base($actor)->whereIn('users.id', $request->ids)->get()
            ->filter(fn (User $t) => Gate::forUser($actor)->allows('returning-officers:delete', $t));

        $deletable->each->delete();

        $skipped = count($request->ids) - $deletable->count();

        return back()->with('success', "{$deletable->count()} deleted" . ($skipped ? ", {$skipped} skipped (not permitted)." : '.'));
    }

    public function export(Request $request)
    {
        Gate::authorize('returning-officers:export');
        $this->enforceFilters($request);

        $query = $this->base($request->user())->with(['profile', 'returningOfficer']);
        $this->applyFilters($query, $request);

        // return Excel::download(new ReturningOfficersExport($query), 'returning-officers.xlsx');
    }

    /* ------------------------------------------------------------------ */

    private function base(User $viewer)
    {
        return User::query()->role(Role::RETURNING_OFFICER->value)->visibleTo($viewer);
    }

    private function profileData(array $data): array
    {
        return array_intersect_key($data, array_flip(self::PROFILE_FIELDS));
    }

    private function officerData(array $data): array
    {
        return ['institution' => $data['institution'], 'posting_location' => $data['posting_location']];
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
                    ->orWhere('phone', 'like', "%{$s}%"))
                ->orWhereHas('returningOfficer', fn ($q) => $q
                    ->where('institution', 'like', "%{$s}%")
                    ->orWhere('posting_location', 'like', "%{$s}%"))))
            ->when($request->state, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('state_id', $v)))
            ->when($request->zone, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('zone_id', $v)))
            ->when($request->lga, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('lga_id', $v)))
            ->when($request->ward, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('ward_id', $v)))
            ->when($request->status, fn ($q, $v) => $q->where('users.is_active', $v === 'active'));
    }

    protected function applySort($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $direction = $request->get('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['institution', 'posting_location'], true)) {
            $query->leftJoin('returning_officers', 'returning_officers.user_id', '=', 'users.id')
                ->orderBy("returning_officers.{$sortBy}", $direction)
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
            'assigned' => ReturningOfficer::whereIn('user_id', $ids())->whereNotNull('posting_location')->count(),
            'institutions' => ReturningOfficer::whereIn('user_id', $ids())->distinct()->count('institution'),
        ];
    }
}