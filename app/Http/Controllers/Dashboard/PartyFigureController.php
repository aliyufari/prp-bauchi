<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AspirantOffice;
use App\Enums\ElectionParty;
use App\Enums\ResultStatus;
use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\Pu;
use App\Models\PuTaskAudit;
use App\Models\PuResultSubmission;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PartyFigureController extends Controller
{
    public function show(Request $request, Pu $pu)
    {
        $user = $request->user();
        abort_unless($user->checkPermissionTo('results.upload'), 403);
        $pu = Pu::query()->visibleTo($user)->with('ward.lga')->whereKey($pu->id)->firstOrFail();
        Gate::forUser($user)->authorize('upload', [Result::class, ['pu_id' => $pu->id]]);

        $officeOptions = $this->officeOptions();
        $office = (string) $request->query('office', $officeOptions[0]['value'] ?? 'governor');
        abort_unless(collect($officeOptions)->contains('value', $office), 404);

        $elections = Election::query()->where('is_active', true)
            ->orderByDesc('date')->orderBy('name')->get(['id', 'name', 'date']);
        $selectedElection = $elections->firstWhere('id', $request->query('election_id')) ?? $elections->first();
        $result = $selectedElection
            ? Result::query()->where('pu_id', $pu->id)->where('election_id', $selectedElection->id)->where('office', $office)->first()
            : null;

        return Inertia::render('portal/pus/Figures', [
            'pu' => [
                'id' => $pu->id,
                'name' => $pu->name,
                'code' => $pu->code,
                'ward' => $pu->ward?->name,
                'lga' => $pu->ward?->lga?->name,
                'registered_voters' => (int) ($pu->registered_voters ?? 0),
            ],
            'elections' => $elections->map(fn (Election $e) => [
                'id' => $e->id,
                'name' => $e->name,
                'date' => $e->date?->toDateString(),
            ])->values(),
            'selectedElectionId' => $selectedElection?->id,
            'offices' => $officeOptions,
            'office' => $office,
            'parties' => collect(ElectionParty::options()),
            'result' => $result ? [
                'id' => $result->id,
                'accredited_voters' => $result->accredited_voters,
                'rejected_votes' => $result->rejected_votes,
                'party_votes' => $result->party_votes ?? [],
                'remarks' => $result->remarks,
                'status' => $result->status?->value,
                'updated_at' => $result->updated_at?->toIso8601String(),
            ] : null,
            'canEdit' => ! $result || $result->status === ResultStatus::PENDING,
        ]);
    }

    public function store(Request $request, Pu $pu)
    {
        $user = $request->user();
        abort_unless($user->checkPermissionTo('results.upload'), 403);
        $pu = Pu::query()->visibleTo($user)->whereKey($pu->id)->firstOrFail();
        Gate::forUser($user)->authorize('upload', [Result::class, ['pu_id' => $pu->id]]);

        $activeOffices = array_column($this->officeOptions(), 'value');
        $data = $request->validate([
            'election_id' => ['required', 'uuid', Rule::exists('elections', 'id')->where('is_active', true)],
            'office' => ['required', Rule::in($activeOffices)],
            'accredited_voters' => ['required', 'integer', 'min:0'],
            'rejected_votes' => ['required', 'integer', 'min:0'],
            'party_votes' => ['required', 'array', 'min:1'],
            'party_votes.*' => ['required', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'result_sheet' => ['nullable', 'image', 'max:10240'],
        ]);

        $validParties = array_column(ElectionParty::cases(), 'value');
        if (array_diff(array_keys($data['party_votes']), $validParties)) {
            return back()->withErrors(['party_votes' => 'One or more selected party codes are not supported.'])->withInput();
        }

        $validVotes = array_sum($data['party_votes']);
        if ($validVotes + $data['rejected_votes'] > $data['accredited_voters']) {
            return back()->withErrors(['party_votes' => 'Valid votes plus rejected votes cannot exceed accredited voters.'])->withInput();
        }
        if ($pu->registered_voters && $data['accredited_voters'] > $pu->registered_voters) {
            return back()->withErrors(['accredited_voters' => 'Accredited voters cannot exceed the registered voters for this polling unit.'])->withInput();
        }

        $submission = PuResultSubmission::query()
            ->where('pu_id', $pu->id)->where('office', $data['office'])->first();
        if ($request->hasFile('result_sheet') && count($submission?->sheets ?? []) >= 8) {
            return back()->withErrors(['result_sheet' => 'This polling unit already has the maximum of 8 result-sheet photos. Remove one before adding another.'])->withInput();
        }

        $election = Election::query()->where('is_active', true)->findOrFail($data['election_id']);
        $existing = Result::query()->where('pu_id', $pu->id)
            ->where('election_id', $election->id)->where('office', $data['office'])->first();

        if ($existing) {
            Gate::forUser($user)->authorize('update', $existing);
        }

        $before = $existing?->only(['accredited_voters', 'rejected_votes', 'party_votes', 'remarks']);
        $sheetPath = null;
        $sheetName = null;

        if ($request->hasFile('result_sheet')) {
            $file = $request->file('result_sheet');
            $sheetName = Str::limit($file->getClientOriginalName(), 120, '');
            $sheetPath = $file->storeAs(
                "pu-evidence/{$pu->id}",
                'sheet-'.Str::uuid().'.'.($file->extension() ?: 'jpg'),
                'local',
            );

            if (! $sheetPath) {
                throw new RuntimeException('The result-sheet photo could not be saved. Please try again.');
            }
        }

        try {
            DB::transaction(function () use ($data, $pu, $election, $existing, $user, $before, $sheetPath, $sheetName) {
                $resultSheetUrl = $existing?->result_sheet_url;

                if ($sheetPath) {
                    $submission = PuResultSubmission::firstOrNew([
                        'pu_id' => $pu->id,
                        'office' => $data['office'],
                    ]);
                    $sheets = $submission->sheets ?? [];
                    $sheetIndex = count($sheets);
                    $sheets[] = ['path' => $sheetPath, 'name' => $sheetName];
                    $submission->sheets = $sheets;
                    $submission->submitted_by = $user->id;
                    $submission->save();

                    $resultSheetUrl = route('pu-media', [
                        $pu->id, 'sheet', $sheetIndex, 'office' => $data['office'],
                    ]);
                }

                $values = [
                    'election_id' => $election->id,
                    'pu_id' => $pu->id,
                    'ward_id' => $pu->ward_id,
                    'office' => $data['office'],
                    'date' => $election->date?->toDateString(),
                    'registered_voters' => (int) ($pu->registered_voters ?? 0),
                    'accredited_voters' => $data['accredited_voters'],
                    'rejected_votes' => $data['rejected_votes'],
                    'party_votes' => $data['party_votes'],
                    'remarks' => $data['remarks'] ?? null,
                    'status' => ResultStatus::PENDING,
                    'result_sheet_url' => $resultSheetUrl,
                ];

                if ($existing) {
                    $existing->fill($values)->save();
                } else {
                    $values['uploaded_by'] = $user->id;
                    Result::create($values);
                }

                PuTaskAudit::create([
                    'pu_id' => $pu->id,
                    'office' => $data['office'],
                    'action' => $existing ? 'party_figures_updated' : 'party_figures_recorded',
                    'details' => [
                        'election_id' => $election->id,
                        'election_name' => $election->name,
                        'result_sheet_attached' => (bool) $sheetPath,
                        'before' => $before,
                        'after' => [
                            'accredited_voters' => $data['accredited_voters'],
                            'rejected_votes' => $data['rejected_votes'],
                            'party_votes' => $data['party_votes'],
                            'remarks' => $data['remarks'] ?? null,
                        ],
                    ],
                    'performed_by' => $user->id,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($sheetPath) {
                Storage::disk('local')->delete($sheetPath);
            }

            throw $exception;
        }

        return redirect()->route('portal.pus.figures', [
            'pu' => $pu->id,
            'election_id' => $election->id,
            'office' => $data['office'],
        ])->with('success', 'Party figures submitted for verification.');
    }

    private function officeOptions(): array
    {
        $active = (array) config('elections.active_offices', ['governor']);

        return collect(AspirantOffice::cases())
            ->filter(fn (AspirantOffice $office) => in_array($office->value, $active, true))
            ->map(fn (AspirantOffice $office) => [
                'value' => $office->value,
                'label' => ucwords(str_replace('_', ' ', $office->value)),
            ])->values()->all();
    }
}
