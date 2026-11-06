<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pu\UpdatePuRequest;
use App\Http\Requests\Pu\StorePuRequest;
use App\Http\Resources\PuResource;
use App\Models\Pu;
use App\Models\User;
use App\Support\ScopedLocations;
use App\Traits\AppliesJurisdiction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use App\Exports\FormattedArrayExport;
use Maatwebsite\Excel\Facades\Excel;

class PuController extends Controller
{
    use AppliesJurisdiction;

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Pu::class);
        $this->enforceFilters($request);
        $user = $request->user();

        $query = Pu::query()->visibleTo($user)->with('ward')->withCount([
            'profiles as agents_count' => fn ($q) => $q->whereHas('user', fn ($u) => $u->role(Role::PARTY_AGENT->value)),
        ]);
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $pus = $query->paginate($request->integer('per_page', 15))->withQueryString();
        $wards = ScopedLocations::options($user)['wards'];

        return Inertia::render('dashboard/polling-units/Index', [
            'pus' => [
                'data' => PuResource::collection($pus->items()),
                'meta' => [
                    'current_page' => $pus->currentPage(), 'last_page' => $pus->lastPage(),
                    'from' => $pus->firstItem(), 'to' => $pus->lastItem(),
                    'per_page' => $pus->perPage(), 'total' => $pus->total(),
                ],
            ],
            'statistics' => $this->statistics($user),
            'filterOptions' => ['wards' => $wards],
            'formOptions' => ['wards' => $wards],
            'filters' => $request->only(['search', 'state', 'zone', 'lga', 'ward']),
        ]);
    }

    public function store(StorePuRequest $request)
    {
        Pu::create($request->validated() + ['registered_voters' => 0]);

        return back()->with('success', 'Polling unit created.');
    }

    public function update(UpdatePuRequest $request, Pu $polling_unit)
    {
        $polling_unit->update($request->validated());

        return back()->with('success', 'Polling unit updated.');
    }

    public function destroy(Pu $polling_unit)
    {
        Gate::authorize('delete', $polling_unit);

        if ($polling_unit->results()->exists()) {
            return back()->with('error', 'This polling unit has results recorded and cannot be deleted.');
        }

        $polling_unit->delete();

        return back()->with('success', 'Polling unit deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['uuid']]);
        $actor = $request->user();

        $deletable = Pu::query()->visibleTo($actor)->whereIn('pus.id', $request->ids)->get()
            ->filter(fn (Pu $p) => Gate::forUser($actor)->allows('delete', $p) && !$p->results()->exists());

        $deletable->each->delete();
        $skipped = count($request->ids) - $deletable->count();

        return back()->with('success', "{$deletable->count()} deleted" . ($skipped ? ", {$skipped} skipped (no permission or has results)." : '.'));
    }

    public function export(Request $request)
    {
        Gate::authorize('viewAny', Pu::class);
        $this->enforceFilters($request);

        $query = Pu::query()->visibleTo($request->user())->with('ward.lga');
        $this->applyFilters($query, $request);
        $this->applySort($query, $request);
        $rows = $query->get()->map(fn (Pu $pu) => [
            $pu->name, $pu->code, $pu->address, $pu->ward?->name,
            $pu->ward?->lga?->name, (int) $pu->registered_voters,
        ])->all();

        return Excel::download(new FormattedArrayExport(
            ['Polling unit', 'Code', 'Address', 'Ward', 'LGA', 'Registered voters'],
            $rows,
            'Polling Units',
        ), 'polling-units.xlsx');
    }

    protected function applyFilters($query, Request $request): void
    {
        $query
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('pus.name', 'like', "%{$s}%")
                ->orWhere('pus.code', 'like', "%{$s}%")
                ->orWhere('pus.address', 'like', "%{$s}%")))
            ->when($request->state, fn ($q, $v) => $q->whereHas('ward.lga.zone', fn ($zone) => $zone->where('zones.state_id', $v)))
            ->when($request->zone, fn ($q, $v) => $q->whereHas('ward.lga', fn ($lga) => $lga->where('lgas.zone_id', $v)))
            ->when($request->lga, fn ($q, $v) => $q->whereHas('ward', fn ($ward) => $ward->where('wards.lga_id', $v)))
            ->when($request->ward, fn ($q, $v) => $q->where('pus.ward_id', $v));
    }

    protected function applySort($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'name');
        $direction = $request->get('sort_direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $sortable = ['name', 'code', 'registered_voters', 'created_at'];

        $query->orderBy('pus.' . (in_array($sortBy, $sortable, true) ? $sortBy : 'name'), $direction);
    }

    protected function statistics(User $user): array
    {
        $base = fn () => Pu::query()->visibleTo($user);

        return [
            'total' => $base()->count(),
            'total_registered_voters' => (int) $base()->sum('pus.registered_voters'),
            'wards_covered' => $base()->whereNotNull('pus.ward_id')->distinct()->count('pus.ward_id'),
            'unassigned' => $base()->whereNull('pus.ward_id')->count(),
        ];
    }
}