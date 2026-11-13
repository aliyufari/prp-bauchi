<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Role;
use App\Enums\StakeholderType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stakeholder\StoreStakeholderRequest;
use App\Http\Requests\Stakeholder\UpdateStakeholderRequest;
use App\Http\Resources\StakeholderResource;
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

class StakeholderController extends Controller
{
    use AppliesJurisdiction, StoresUserAvatar;

    private const PROFILE_FIELDS = [
        'phone', 'gender', 'occupation', 'education_status', 'application_id',
        'state_id', 'zone_id', 'lga_id', 'ward_id', 'pu_id',
    ];

    public function index(Request $request)
    {
        Gate::authorize('stakeholders:viewAny');
        $this->enforceFilters($request);
        $user = $request->user();

        $query = $this->base($user)->with(['profile.state', 'profile.zone', 'profile.lga', 'profile.ward', 'profile.pu', 'stakeholder']);
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $stakeholders = $query->paginate($request->integer('per_page', 15))->withQueryString();
        $locations = ScopedLocations::options($user);

        return Inertia::render('dashboard/stakeholders/Index', [
            'stakeholders' => [
                'data' => StakeholderResource::collection($stakeholders->items()),
                'meta' => [
                    'current_page' => $stakeholders->currentPage(),
                    'last_page' => $stakeholders->lastPage(),
                    'from' => $stakeholders->firstItem(),
                    'to' => $stakeholders->lastItem(),
                    'per_page' => $stakeholders->perPage(),
                    'total' => $stakeholders->total(),
                ],
            ],
            'statistics' => $this->statistics($user),
            'filterOptions' => $locations + [
                'statuses' => [['value' => 'active', 'label' => 'Active'], ['value' => 'inactive', 'label' => 'Inactive']],
                'types' => $this->typeOptions(),
            ],
            'formOptions' => [
                'types' => $this->typeOptions(),
            ],
            'filters' => $request->only(['search', 'state', 'zone', 'lga', 'ward', 'pu', 'status', 'type']),
        ]);
    }

    public function store(StoreStakeholderRequest $request)
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

            $user->assignRole(Role::STAKEHOLDER->value);
            $user->profile()->create($this->profileData($data));
            $user->stakeholder()->create($this->stakeholderData($data));
            $this->syncUserAvatar($user, $request);
        });

        return back()->with('success', 'Stakeholder created.');
    }

    public function update(UpdateStakeholderRequest $request, User $stakeholder)
    {
        DB::transaction(function () use ($request, $stakeholder) {
            $data = $request->validated();

            $stakeholder->update([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                ...(!empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
            ]);

            $stakeholder->profile()->updateOrCreate(['user_id' => $stakeholder->id], $this->profileData($data));
            $stakeholder->stakeholder()->updateOrCreate(['user_id' => $stakeholder->id], $this->stakeholderData($data));
            $this->syncUserAvatar($stakeholder, $request);
        });

        return back()->with('success', 'Stakeholder updated.');
    }

    public function show(User $stakeholder)
    {
        Gate::authorize('stakeholders:view', $stakeholder);

        $stakeholder->load(['profile.state', 'profile.zone', 'profile.lga', 'profile.ward', 'profile.pu', 'stakeholder']);

        return Inertia::render('dashboard/stakeholders/Show', ['stakeholder' => new StakeholderResource($stakeholder)]);
    }

    public function destroy(User $stakeholder)
    {
        Gate::authorize('stakeholders:delete', $stakeholder);

        $stakeholder->delete();

        return back()->with('success', 'Stakeholder deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['uuid']]);
        $actor = $request->user();

        $deletable = $this->base($actor)->whereIn('users.id', $request->ids)->get()
            ->filter(fn (User $t) => Gate::forUser($actor)->allows('stakeholders:delete', $t));

        $deletable->each->delete();

        $skipped = count($request->ids) - $deletable->count();

        return back()->with('success', "{$deletable->count()} deleted" . ($skipped ? ", {$skipped} skipped (not permitted)." : '.'));
    }

    public function export(Request $request)
    {
        Gate::authorize('stakeholders:export');
        $this->enforceFilters($request);

        $query = $this->base($request->user())->with(['profile.lga', 'profile.ward', 'profile.pu', 'stakeholder']);
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);
        $rows = ExportRows::people($query->get(), 'stakeholder', ['type', 'organization', 'designation']);

        return Excel::download(new FormattedArrayExport(['Full name', 'Email', 'Phone', 'Gender', 'Occupation', 'Education level', 'LGA', 'Ward', 'Polling unit', 'Status', 'Type', 'Organization', 'Designation'], $rows, 'Stakeholders'), 'stakeholders.xlsx');
    }

    /* ------------------------------------------------------------------ */

    private function base(User $viewer)
    {
        return User::query()->role(Role::STAKEHOLDER->value)->visibleTo($viewer);
    }

    private function profileData(array $data): array
    {
        return array_intersect_key($data, array_flip(self::PROFILE_FIELDS));
    }

    private function stakeholderData(array $data): array
    {
        return [
            'type' => $data['type'],
            'organization' => $data['organization'] ?? null,
            'designation' => $data['designation'] ?? null,
        ];
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
                ->orWhereHas('stakeholder', fn ($q) => $q->where('organization', 'like', "%{$s}%"))))
            ->when($request->state, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('state_id', $v)))
            ->when($request->zone, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('zone_id', $v)))
            ->when($request->lga, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('lga_id', $v)))
            ->when($request->ward, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('ward_id', $v)))
            ->when($request->pu, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('pu_id', $v)))
            ->when($request->status, fn ($q, $v) => $q->where('users.is_active', $v === 'active'))
            ->when($request->type, fn ($q, $v) => $q->whereHas('stakeholder', fn ($q) => $q->where('type', $v)));
    }

    protected function applySort($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $direction = $request->get('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'stakeholder_type') {
            $query->leftJoin('stakeholders', 'stakeholders.user_id', '=', 'users.id')
                ->orderBy('stakeholders.type', $direction)
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
            'traditional_rulers' => $base()->whereHas('stakeholder', fn ($q) => $q->where('type', StakeholderType::TRADITIONAL_RULER))->count(),
            'organizations' => $base()->whereHas('stakeholder', fn ($q) => $q->whereNotNull('organization'))->count(),
        ];
    }

    protected function typeOptions(): array
    {
        return collect(StakeholderType::cases())
            ->map(fn ($c) => ['value' => $c->value, 'label' => ucwords(str_replace('_', ' ', strtolower($c->value)))])
            ->values()->all();
    }
}