<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AspirantOffice;
use App\Enums\ElectionParty;
use App\Enums\ResultStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Result\StoreResultRequest;
use App\Http\Requests\Result\UpdateResultRequest;
use App\Http\Resources\ResultResource;
use App\Models\Pu;
use App\Models\Result;
use App\Models\User;
use App\Support\ScopedLocations;
use App\Traits\AppliesJurisdiction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ResultController extends Controller
{
    use AppliesJurisdiction;

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Result::class);
        $this->enforceFilters($request);
        $user = $request->user();

        $query = Result::query()->visibleTo($user)
            ->with(['pu', 'ward', 'lga', 'zone', 'state', 'uploader', 'verifier']);
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $results = $query->paginate($request->integer('per_page', 15))->withQueryString();
        $states = ScopedLocations::options($user)['states'];

        return Inertia::render('dashboard/results/Index', [
            'results' => [
                'data' => ResultResource::collection($results->items()),
                'meta' => [
                    'current_page' => $results->currentPage(), 'last_page' => $results->lastPage(),
                    'from' => $results->firstItem(), 'to' => $results->lastItem(),
                    'per_page' => $results->perPage(), 'total' => $results->total(),
                ],
            ],
            'statistics' => $this->statistics($user),
            'filterOptions' => ['offices' => $this->officeOptions(), 'statuses' => $this->statusOptions(), 'states' => $states],
            'formOptions' => ['offices' => $this->officeOptions(), 'parties' => $this->partyOptions(), 'states' => $states],
            'filters' => $request->only(['search', 'office', 'status', 'state']),
            'canUpload' => $user->checkPermissionTo('results.upload'),
        ]);
    }

    public function store(StoreResultRequest $request)
    {
        $data = $request->validated();
        $pu = Pu::findOrFail($data['pu_id']);

        // The observer fills ward/lga/zone/state from the PU.
        Result::create($data + [
            'registered_voters' => $data['registered_voters'] ?? $pu->registered_voters ?? 0,
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Result uploaded.');
    }

    public function update(UpdateResultRequest $request, Result $result)
    {
        $result->update($request->validated());

        return back()->with('success', 'Result updated.');
    }

    public function verify(Request $request, Result $result)
    {
        Gate::authorize('verify', $result);

        $request->validate(['status' => ['required', Rule::enum(ResultStatus::class)]]);

        $result->update([
            'status' => $request->status,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Result status updated.');
    }

    public function destroy(Result $result)
    {
        Gate::authorize('delete', $result);

        $result->delete();

        return back()->with('success', 'Result deleted.');
    }

    protected function applyFilters($query, Request $request): void
    {
        $query
            ->when($request->search, fn ($q, $s) => $q->whereHas('pu', fn ($p) => $p
                ->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%")))
            ->when($request->office, fn ($q, $v) => $q->where('results.office', $v))
            ->when($request->status, fn ($q, $v) => $q->where('results.status', $v))
            ->when($request->state, fn ($q, $v) => $q->where('results.state_id', $v));
    }

    protected function applySort($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $direction = $request->get('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $sortable = ['created_at', 'accredited_voters', 'total_valid_votes'];

        $query->orderBy('results.' . (in_array($sortBy, $sortable, true) ? $sortBy : 'created_at'), $direction);
    }

    protected function statistics(User $user): array
    {
        $count = fn (?ResultStatus $s = null) => Result::query()->visibleTo($user)
            ->when($s, fn ($q) => $q->where('results.status', $s->value))->count();

        return [
            'total' => $count(),
            'verified' => $count(ResultStatus::VERIFIED),
            'pending' => $count(ResultStatus::PENDING),
            'disputed' => $count(ResultStatus::DISPUTED),
        ];
    }

    protected function officeOptions(): array
    {
        return collect(AspirantOffice::cases())
            ->map(fn ($c) => ['value' => $c->value, 'label' => ucwords(str_replace('_', ' ', strtolower($c->value)))])->values()->all();
    }

    protected function statusOptions(): array
    {
        return collect(ResultStatus::cases())
            ->map(fn ($c) => ['value' => $c->value, 'label' => ucfirst(strtolower($c->value))])->values()->all();
    }

    protected function partyOptions(): array
    {
        return collect(ElectionParty::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->value])->values()->all();
    }
}