<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pu\StorePuRequest;
use App\Http\Requests\Pu\UpdatePuRequest;
use App\Http\Resources\PuResource;
use App\Models\Pu;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PuController extends Controller
{
    public function index(Request $request)
    {
        $query = Pu::query()
            ->with('ward')
            ->withCount(['profiles as agents_count' => function ($q) {
                // Counts any user_profiles row pointing at this PU —
                // a reasonable proxy for "agents/members deployed here"
                // until/unless you want to scope this to party agents only.
            }]);

        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $pus = $query->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return Inertia::render('dashboard/polling-units/Index', [
            'pus' => [
                'data' => PuResource::collection($pus->items()),
                'meta' => [
                    'current_page' => $pus->currentPage(),
                    'last_page' => $pus->lastPage(),
                    'from' => $pus->firstItem(),
                    'to' => $pus->lastItem(),
                    'per_page' => $pus->perPage(),
                    'total' => $pus->total(),
                ],
            ],
            'statistics' => $this->statistics(),
            'filterOptions' => [
                'wards' => Ward::orderBy('name')->get(['id', 'name']),
            ],
            'formOptions' => [
                'wards' => Ward::orderBy('name')->get(['id', 'name']),
            ],
            'filters' => $request->only(['search', 'ward']),
        ]);
    }

    public function store(StorePuRequest $request)
    {
        Pu::create($request->validated());

        return back()->with('success', 'Polling unit created.');
    }

    public function update(UpdatePuRequest $request, Pu $polling_unit)
    {
        $polling_unit->update($request->validated());

        return back()->with('success', 'Polling unit updated.');
    }

    public function show(Pu $polling_unit)
    {
        $polling_unit->load('ward');

        return Inertia::render('dashboard/polling-units/Show', [
            'pu' => new PuResource($polling_unit),
        ]);
    }

    public function destroy(Pu $polling_unit)
    {
        $polling_unit->delete();

        return back()->with('success', 'Polling unit deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['uuid']]);

        Pu::whereIn('id', $request->ids)->delete();

        return back()->with('success', 'Selected polling units deleted.');
    }

    public function export(Request $request)
    {
        $query = Pu::query()->with('ward');
        $this->applyFilters($query, $request);

        // return Excel::download(new PollingUnitsExport($query), 'polling-units.xlsx');
    }

    protected function applyFilters($query, Request $request): void
    {
        $query
            ->when($request->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            }))
            ->when($request->ward, fn ($q, $v) => $q->where('ward_id', $v));
    }

    protected function applySort($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'name');
        $direction = $request->get('sort_direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $sortable = ['name', 'code', 'registered_voters', 'created_at'];
        $query->orderBy(in_array($sortBy, $sortable, true) ? $sortBy : 'name', $direction);
    }

    protected function statistics(): array
    {
        return [
            'total' => Pu::count(),
            'total_registered_voters' => (int) Pu::sum('registered_voters'),
            'wards_covered' => Pu::whereNotNull('ward_id')->distinct('ward_id')->count('ward_id'),
            'unassigned' => Pu::whereNull('ward_id')->count(),
        ];
    }
}