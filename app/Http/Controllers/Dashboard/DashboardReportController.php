<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Role;
use App\Exports\FormattedArrayExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pu;
use App\Models\Result;
use App\Enums\ResultStatus;
use App\Support\AspirantScope;
use App\Support\LocationChain;
use App\Support\ScopedLocations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class DashboardReportController extends Controller
{
    public function __invoke(Request $request)
    {
        Gate::authorize('dashboard.view');
        $filters = $request->validate(['lga' => ['nullable', 'uuid'], 'ward' => ['nullable', 'uuid']]);
        $stateId = LocationChain::bauchiStateId();
        abort_unless($stateId, 503, config('location.operating_state', 'Bauchi').' State locations have not been seeded.');
        $lgaId = $filters['lga'] ?? null;
        $wardId = $filters['ward'] ?? null;

        abort_unless(!$lgaId || DB::table('lgas')->where('id', $lgaId)->whereIn('zone_id', LocationChain::within('zone', 'state', $stateId))->exists(), 404);
        abort_unless(!$wardId || DB::table('wards')->where('id', $wardId)->whereIn('lga_id', LocationChain::within('lga', 'state', $stateId))->when($lgaId, fn ($q) => $q->where('lga_id', $lgaId))->exists(), 404);

        $viewer = $request->user();
        $isAspirant = AspirantScope::is($viewer);

        // Aspirants report on their own people; polling units and results are state facts they may see in full.
        $people = fn (Role $role) => ($isAspirant
            ? User::role($role->value)->whereIn('users.id', AspirantScope::peopleIds($viewer))
            : User::role($role->value)->visibleTo($viewer))
            ->when($lgaId, fn ($q) => $q->whereHas('profile', fn ($p) => $p->where('lga_id', $lgaId)))
            ->when($wardId, fn ($q) => $q->whereHas('profile', fn ($p) => $p->where('ward_id', $wardId)));
        $pollingUnits = fn () => ($isAspirant ? Pu::query() : Pu::visibleTo($viewer))
            ->when($lgaId, fn ($q) => $q->whereHas('ward', fn ($w) => $w->where('lga_id', $lgaId)))
            ->when($wardId, fn ($q) => $q->where('ward_id', $wardId));
        $results = fn () => ($isAspirant ? Result::query() : Result::query()->visibleTo($viewer))
            ->when($lgaId, fn ($q) => $q->where('results.lga_id', $lgaId))
            ->when($wardId, fn ($q) => $q->where('results.ward_id', $wardId));

        $rows = [
            ['Dashboard summary', 'Total members', $people(Role::MEMBER)->count()],
            ['Dashboard summary', 'Party agents', $people(Role::PARTY_AGENT)->count()],
            ['Dashboard summary', 'Coordinators', $people(Role::COORDINATOR)->count()],
            ['Dashboard summary', 'Stakeholders', $people(Role::STAKEHOLDER)->count()],
            ['Dashboard summary', 'LGAs covered', DB::table('user_profiles')->whereIn('user_id', $people(Role::MEMBER)->select('users.id'))->whereNotNull('lga_id')->distinct()->count('lga_id')],
            ['Dashboard summary', 'Polling units', $pollingUnits()->count()],
            ['Dashboard summary', 'Registered voters', (int) $pollingUnits()->sum('pus.registered_voters')],
            ['Results', 'Total results', $results()->count()],
            ['Results', 'Verified results', $results()->where('results.status', ResultStatus::VERIFIED->value)->count()],
            ['Results', 'Pending results', $results()->where('results.status', ResultStatus::PENDING->value)->count()],
            ['Results', 'Disputed results', $results()->where('results.status', ResultStatus::DISPUTED->value)->count()],
        ];

        $byLga = DB::table('user_profiles as p')->join('lgas as l', 'l.id', '=', 'p.lga_id')
            ->whereIn('p.user_id', $people(Role::MEMBER)->select('users.id'))
            ->when($lgaId, fn ($q) => $q->where('p.lga_id', $lgaId))
            ->when($wardId, fn ($q) => $q->where('p.ward_id', $wardId))
            ->groupBy('l.name')->orderBy('l.name')->selectRaw('l.name as name, count(*) as total')->get();

        foreach ($byLga as $item) {
            $rows[] = ['Membership by LGA', $item->name, (int) $item->total];
        }

        $filterNames = ScopedLocations::options($request->user());
        $selectedLga = $lgaId ? $filterNames['lgas']->firstWhere('id', $lgaId)?->name : 'All LGAs';
        $selectedWard = $wardId ? $filterNames['wards']->firstWhere('id', $wardId)?->name : 'All wards';
        array_unshift($rows, ['Applied filters', 'LGA', $selectedLga], ['Applied filters', 'Ward', $selectedWard]);

        return Excel::download(new FormattedArrayExport(['Report section', 'Metric', 'Value'], $rows, 'Dashboard Report'), 'bauchi-dashboard-report.xlsx');
    }
}
