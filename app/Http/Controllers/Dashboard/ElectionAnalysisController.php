<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AspirantOffice;
use App\Enums\ElectionParty;
use App\Enums\ResultStatus;
use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\Pu;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ElectionAnalysisController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAnalysis($request);

        $elections = Election::query()->orderByDesc('date')->orderBy('name')->get();
        $election = $elections->firstWhere('id', $request->query('election_id')) ?? $elections->first();
        $offices = collect(AspirantOffice::cases())->map(fn ($office) => [
            'value' => $office->value,
            'label' => ucwords(str_replace('_', ' ', $office->value)),
        ])->values();
        $office = (string) $request->query('office', $offices->first()['value'] ?? 'governor');
        abort_unless($offices->contains(fn ($item) => $item['value'] === $office), 404);
        $includePending = $request->boolean('include_pending');

        $allResults = $election
            ? Result::query()->with(['pu.ward.lga', 'uploader', 'verifier'])
                ->where('election_id', $election->id)->where('office', $office)
                ->orderBy('created_at')->get()
            : collect();

        $counted = $allResults->filter(fn (Result $result) => $includePending
            ? $result->status !== ResultStatus::DISPUTED
            : $result->status === ResultStatus::VERIFIED);

        $totals = collect(ElectionParty::cases())->mapWithKeys(fn (ElectionParty $party) => [$party->value => 0]);
        foreach ($counted as $result) {
            foreach (($result->party_votes ?? []) as $party => $votes) {
                if ($totals->has($party)) {
                    $totals[$party] += (int) $votes;
                }
            }
        }

        $totalPollingUnits = Pu::query()->count();
        $allUnitsVerified = $totalPollingUnits > 0
            && $allResults->count() >= $totalPollingUnits
            && $allResults->every(fn (Result $result) => $result->status === ResultStatus::VERIFIED);

        $maxVotes = $totals->max() ?? 0;
        $leaders = $maxVotes > 0 ? $totals->filter(fn ($votes) => $votes === $maxVotes)->keys()->values() : collect();
        $winner = $leaders->isEmpty() ? null : [
            'label' => $leaders->count() > 1 ? 'Tie: '.$leaders->implode(', ') : $leaders->first(),
            'parties' => $leaders->all(),
            'votes' => $maxVotes,
        ];

        return Inertia::render('dashboard/election-analysis/Index', [
            'elections' => $elections->map(fn (Election $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'date' => $item->date?->toDateString(),
                'is_active' => $item->is_active,
            ])->values(),
            'offices' => $offices,
            'parties' => collect(ElectionParty::options()),
            'selectedElectionId' => $election?->id,
            'selectedOffice' => $office,
            'includePending' => $includePending,
            'summary' => [
                'submitted' => $allResults->count(),
                'verified' => $allResults->filter(fn (Result $result) => $result->status === ResultStatus::VERIFIED)->count(),
                'pending' => $allResults->filter(fn (Result $result) => $result->status === ResultStatus::PENDING)->count(),
                'disputed' => $allResults->filter(fn (Result $result) => $result->status === ResultStatus::DISPUTED)->count(),
                'counted' => $counted->count(),
                'polling_units' => $totalPollingUnits,
                'complete' => $allUnitsVerified,
                'valid_votes' => $totals->sum(),
                'accredited_voters' => $counted->sum('accredited_voters'),
                'rejected_votes' => $counted->sum('rejected_votes'),
            ],
            'partyTotals' => $totals->map(fn ($votes, $party) => ['party' => $party, 'votes' => $votes])->values(),
            'winner' => $winner,
            'rows' => $allResults->map(fn (Result $result) => [
                'id' => $result->id,
                'pu' => $result->pu?->name,
                'code' => $result->pu?->code,
                'ward' => $result->pu?->ward?->name,
                'lga' => $result->pu?->ward?->lga?->name,
                'status' => $result->status?->value,
                'party_votes' => $result->party_votes ?? [],
                'valid_votes' => $result->total_valid_votes,
                'accredited_voters' => $result->accredited_voters,
                'rejected_votes' => $result->rejected_votes,
            ])->values(),
        ]);
    }

    public function storeElection(Request $request)
    {
        $this->authorizeAnalysis($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160', 'unique:elections,name'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $election = Election::create($data + ['is_active' => true]);

        return redirect()->route('election-analysis.index', ['election_id' => $election->id])
            ->with('success', 'Election created. Agents can now submit party figures for it.');
    }

    public function toggleElection(Request $request, Election $election)
    {
        $this->authorizeAnalysis($request);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $election->update(['is_active' => (bool) $data['is_active']]);

        return back()->with('success', $election->is_active
            ? 'Election activated. Party agents can submit figures.'
            : 'Election closed for new party-figure submissions.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorizeAnalysis($request);
        $data = $request->validate([
            'election_id' => ['required', 'uuid', 'exists:elections,id'],
            'office' => ['required', Rule::in(array_column(AspirantOffice::cases(), 'value'))],
        ]);
        $election = Election::findOrFail($data['election_id']);
        $parties = array_column(ElectionParty::cases(), 'value');

        return response()->streamDownload(function () use ($election, $data, $parties) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_merge([
                'Polling Unit', 'PU Code', 'Ward', 'LGA', 'Election', 'Election Date', 'Office', 'Status',
                'Registered Voters', 'Accredited Voters',
            ], $parties, ['Total Valid Votes', 'Rejected Votes', 'Uploaded By', 'Verified By', 'Submitted At']), ',', '"', '\\');

            Result::query()->with(['pu.ward.lga', 'uploader', 'verifier'])
                ->where('election_id', $election->id)->where('office', $data['office'])
                ->orderBy('id')->chunk(500, function ($results) use ($out, $election, $parties) {
                    foreach ($results as $result) {
                        $votes = $result->party_votes ?? [];
                        $row = [
                            $result->pu?->name,
                            $result->pu?->code,
                            $result->pu?->ward?->name,
                            $result->pu?->ward?->lga?->name,
                            $election->name,
                            $election->date?->toDateString(),
                            $result->office?->value ?? $result->office,
                            $result->status?->value ?? $result->status,
                            $result->registered_voters,
                            $result->accredited_voters,
                        ];
                        foreach ($parties as $party) {
                            $row[] = (int) ($votes[$party] ?? 0);
                        }
                        $row[] = $result->total_valid_votes;
                        $row[] = $result->rejected_votes;
                        $row[] = $result->uploader ? trim($result->uploader->first_name.' '.$result->uploader->last_name) : '';
                        $row[] = $result->verifier ? trim($result->verifier->first_name.' '.$result->verifier->last_name) : '';
                        $row[] = $result->created_at?->toDateTimeString();
                        fputcsv($out, $row, ',', '"', '\\');
                    }
                });
            fclose($out);
        }, 'election-results-'.Str::slug($election->name).'-'.Str::slug($data['office']).'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function authorizeAnalysis(Request $request): void
    {
        abort_unless($request->user()?->checkPermissionTo('results.analyse'), 403);
    }
}
