<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\EducationStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Http\Resources\MemberResource;
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

class MemberController extends Controller
{
    use AppliesJurisdiction, StoresUserAvatar;

    private const PROFILE_FIELDS = [
        'phone', 'gender', 'occupation', 'education_status', 'application_id',
        'state_id', 'zone_id', 'lga_id', 'ward_id', 'pu_id',
        'mentor_name', 'mentor_status', 'training_status',
    ];

    public function index(Request $request)
    {
        Gate::authorize('members:viewAny');
        $this->enforceFilters($request);
        $user = $request->user();

        $query = $this->base($user)->with(['profile.state', 'profile.zone', 'profile.lga', 'profile.ward', 'profile.pu']);
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $members = $query->paginate($request->integer('per_page', 15))->withQueryString();
        $locations = ScopedLocations::options($user);

        return Inertia::render('dashboard/members/Index', [
            'members' => [
                'data' => MemberResource::collection($members->items()),
                'meta' => [
                    'current_page' => $members->currentPage(),
                    'last_page' => $members->lastPage(),
                    'from' => $members->firstItem(),
                    'to' => $members->lastItem(),
                    'per_page' => $members->perPage(),
                    'total' => $members->total(),
                ],
            ],
            'statistics' => $this->statistics($user),
            'filterOptions' => $locations + [
                'statuses' => [['value' => 'active', 'label' => 'Active'], ['value' => 'inactive', 'label' => 'Inactive']],
            ],
            'formOptions' => [
                'educationStatuses' => EducationStatus::options(),
            ],
            'filters' => $request->only(['search', 'state', 'zone', 'lga', 'ward', 'pu', 'status']),
        ]);
    }

    public function store(StoreMemberRequest $request)
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

            $user->assignRole(Role::MEMBER->value);
            $user->profile()->create($this->profileData($data)); // observer completes the chain
            $this->syncUserAvatar($user, $request);
        });

        return back()->with('success', 'Member created.');
    }

    public function update(UpdateMemberRequest $request, User $member)
    {
        DB::transaction(function () use ($request, $member) {
            $data = $request->validated();

            $member->update([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                ...(!empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
            ]);

            $member->profile()->updateOrCreate(['user_id' => $member->id], $this->profileData($data));
            $this->syncUserAvatar($member, $request);
        });

        return back()->with('success', 'Member updated.');
    }

    public function show(User $member)
    {
        Gate::authorize('members:view', $member);

        $member->load(['profile.state', 'profile.zone', 'profile.lga', 'profile.ward', 'profile.pu']);

        return Inertia::render('dashboard/members/Show', ['member' => new MemberResource($member)]);
    }

    public function destroy(User $member)
    {
        Gate::authorize('members:delete', $member);

        $member->delete();

        return back()->with('success', 'Member deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['uuid']]);
        $actor = $request->user();

        // Only rows inside the actor's scope are even loaded; each is then policy-checked.
        $deletable = $this->base($actor)->whereIn('id', $request->ids)->get()
            ->filter(fn (User $t) => Gate::forUser($actor)->allows('members:delete', $t));

        $deletable->each->delete();

        $skipped = count($request->ids) - $deletable->count();

        return back()->with('success', "{$deletable->count()} deleted" . ($skipped ? ", {$skipped} skipped (not permitted)." : '.'));
    }

    public function export(Request $request)
    {
        Gate::authorize('members:export');
        $this->enforceFilters($request);

        $query = $this->base($request->user())->with(['profile.lga', 'profile.ward', 'profile.pu']);
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);
        $rows = ExportRows::people($query->get(), '', []);

        return Excel::download(new FormattedArrayExport(['Full name', 'Email', 'Phone', 'Gender', 'Occupation', 'Education level', 'LGA', 'Ward', 'Polling unit', 'Status'], $rows, 'Members'), 'members.xlsx');
    }

    /* ------------------------------------------------------------------ */

    private function base(User $viewer)
    {
        return User::query()->role(Role::MEMBER->value)->visibleTo($viewer);
    }

    private function profileData(array $data): array
    {
        return array_intersect_key($data, array_flip(self::PROFILE_FIELDS)) + [
            'mentor_status' => false,
            'training_status' => false,
        ];
    }

    protected function applyFilters($query, Request $request): void
    {
        $query
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhereHas('profile', fn ($q) => $q
                    ->where('application_id', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%"))))
            ->when($request->state, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('state_id', $v)))
            ->when($request->zone, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('zone_id', $v)))
            ->when($request->lga, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('lga_id', $v)))
            ->when($request->ward, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('ward_id', $v)))
            ->when($request->pu, fn ($q, $v) => $q->whereHas('profile', fn ($q) => $q->where('pu_id', $v)))
            ->when($request->status, fn ($q, $v) => $q->where('is_active', $v === 'active'));
    }

    protected function applySort($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $direction = $request->get('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['training_status', 'mentor_name', 'application_id'], true)) {
            $query->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
                ->orderBy("user_profiles.{$sortBy}", $direction)
                ->select('users.*');

            return;
        }

        $sortable = ['first_name', 'last_name', 'email', 'is_active', 'created_at'];
        $query->orderBy(in_array($sortBy, $sortable, true) ? $sortBy : 'created_at', $direction);
    }

    protected function statistics(User $viewer): array
    {
        $profile = fn (string $col) => $this->base($viewer)->whereHas('profile', fn ($q) => $q->where($col, true))->count();

        return [
            'total' => $this->base($viewer)->count(),
            'active' => $this->base($viewer)->where('is_active', true)->count(),
            'trained' => $profile('training_status'),
            'mentored' => $profile('mentor_status'),
        ];
    }
}