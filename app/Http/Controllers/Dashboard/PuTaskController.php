<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AspirantOffice;
use App\Http\Controllers\Controller;
use App\Models\Pu;
use App\Models\PuOfficial;
use App\Models\PuResultSubmission;
use App\Models\User;
use App\Support\Jurisdiction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Party-agent field tasks for a polling unit:
 *  1. capture the officials (PO, APO1, APO2, security),
 *  2. upload the counting video and photos of the result sheet.
 *
 * A PU agent works on his own PU; a ward agent works on every PU in his ward.
 * Scope is enforced by Pu::visibleTo (Jurisdiction), never by the URL.
 */
class PuTaskController extends Controller
{
    private const DISK = 'local';          // private disk, served only through media()
    private const MAX_SHEETS = 8;

    /* ---------------------------------------------------------------- agent */

    public function index(Request $request)
    {
        $user = $this->agent($request);
        $j = Jurisdiction::for($user);
        $office = $this->office($request);

        $query = Pu::query()->visibleTo($user)->with(['ward', 'official', 'submission' => $this->forOffice($office)])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('pus.name', 'like', "%{$s}%")->orWhere('pus.code', 'like', "%{$s}%")))
            ->when($request->status === 'officials', fn ($q) => $q->whereDoesntHave('official'))
            ->when($request->status === 'result', fn ($q) => $q->resultIncomplete($office->value))
            ->orderBy('pus.name');

        $pus = $query->paginate(12)->withQueryString();

        return Inertia::render('portal/pus/Index', [
            'scope' => $j->level === 'pu' ? 'pu' : 'ward',
            'scopeName' => $j->name(),
            'pus' => [
                'data' => collect($pus->items())->map(fn (Pu $pu) => $this->summary($pu))->values(),
                'meta' => [
                    'current_page' => $pus->currentPage(), 'last_page' => $pus->lastPage(),
                    'from' => $pus->firstItem(), 'to' => $pus->lastItem(),
                    'per_page' => $pus->perPage(), 'total' => $pus->total(),
                ],
            ],
            'filters' => $request->only(['search', 'status']),
            'office' => $office->value,
            'offices' => $this->officeOptions(),
        ]);
    }

    public function officials(Request $request, Pu $pu)
    {
        $this->authorizePu($request, $pu);
        $pu->load(['ward.lga', 'official']);

        return Inertia::render('portal/pus/Officials', [
            'pu' => $this->puInfo($pu),
            'official' => $pu->official?->only(['po_name', 'apo1_name', 'apo2_name', 'security_name']),
            'updatedAt' => $pu->official?->updated_at?->toIso8601String(),
        ]);
    }

    public function saveOfficials(Request $request, Pu $pu)
    {
        $this->authorizePu($request, $pu);

        $data = $request->validate([
            'po_name' => ['required', 'string', 'max:120'],
            'apo1_name' => ['required', 'string', 'max:120'],
            'apo2_name' => ['required', 'string', 'max:120'],
            'security_name' => ['required', 'string', 'max:120'],
        ]);

        PuOfficial::updateOrCreate(['pu_id' => $pu->id], $data + ['updated_by' => $request->user()->id]);

        return redirect()->route('portal.pus')->with('success', "Officials saved for {$pu->name}.");
    }

    public function result(Request $request, Pu $pu)
    {
        $this->authorizePu($request, $pu);
        $office = $this->office($request);
        $pu->load(['ward.lga', 'submission' => $this->forOffice($office), 'submission.submitter']);

        return Inertia::render('portal/pus/Result', [
            'pu' => $this->puInfo($pu),
            'office' => $office->value,
            'officeLabel' => $this->officeLabel($office),
            'submission' => $this->submissionPayload($pu->submission),
            'limits' => ['video_mb' => 150, 'sheet_mb' => 10, 'max_sheets' => self::MAX_SHEETS],
        ]);
    }

    public function saveResult(Request $request, Pu $pu)
    {
        $this->authorizePu($request, $pu);
        $office = $this->office($request);
        $submission = $pu->submissions()->where('office', $office->value)->first();
        $existing = count($submission?->sheets ?? []);

        $data = $request->validate([
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm,video/3gpp,video/x-matroska', 'max:153600'],
            'sheets' => ['nullable', 'array', 'max:'.max(0, self::MAX_SHEETS - $existing)],
            'sheets.*' => ['image', 'max:10240'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'sheets.max' => 'You can keep at most '.self::MAX_SHEETS.' result-sheet photos. Remove one first.',
            'video.max' => 'The video is larger than 150 MB. Record a shorter clip or lower the camera quality.',
            'video.mimetypes' => 'Upload a video file (mp4, mov, webm or 3gp).',
            'sheets.*.image' => 'Result sheet files must be photos.',
            'sheets.*.max' => 'Each photo must be 10 MB or less.',
        ]);

        if (! $request->hasFile('video') && empty($data['sheets']) && ! $submission) {
            return back()->withErrors(['video' => 'Add the counting video or at least one result-sheet photo.']);
        }

        $dir = "pu-evidence/{$pu->id}";
        $submission ??= new PuResultSubmission(['pu_id' => $pu->id, 'office' => $office->value]);

        if ($request->hasFile('video')) {
            $this->forget($submission->video_path);
            $file = $request->file('video');
            $submission->video_path = $file->storeAs($dir, 'video-'.Str::uuid().'.'.($file->extension() ?: 'mp4'), self::DISK);
            $submission->video_name = Str::limit($file->getClientOriginalName(), 120, '');
        }

        $sheets = $submission->sheets ?? [];
        foreach ($request->file('sheets', []) as $photo) {
            $sheets[] = [
                'path' => $photo->storeAs($dir, 'sheet-'.Str::uuid().'.'.($photo->extension() ?: 'jpg'), self::DISK),
                'name' => Str::limit($photo->getClientOriginalName(), 120, ''),
            ];
        }

        $submission->sheets = array_values($sheets);
        $submission->remarks = $data['remarks'] ?? $submission->remarks;
        $submission->submitted_by = $request->user()->id;
        $submission->save();

        return redirect()->route('portal.pus', ['office' => $office->value])->with('success', "{$this->officeLabel($office)} result evidence uploaded for {$pu->name}.");
    }

    public function deleteFile(Request $request, Pu $pu)
    {
        $this->authorizePu($request, $pu);
        $request->validate(['kind' => ['required', Rule::in(['video', 'sheet'])], 'index' => ['nullable', 'integer', 'min:0']]);

        $submission = $pu->submissions()->where('office', $this->office($request)->value)->first();
        abort_unless($submission, 404);

        if ($request->kind === 'video') {
            $this->forget($submission->video_path);
            $submission->video_path = $submission->video_name = null;
        } else {
            $sheets = $submission->sheets ?? [];
            $i = $request->integer('index');
            abort_unless(isset($sheets[$i]), 404);
            $this->forget($sheets[$i]['path']);
            unset($sheets[$i]);
            $submission->sheets = array_values($sheets);
        }

        $submission->save();

        return back()->with('success', 'File removed.');
    }

    /* --------------------------------------------------------------- media */

    /** Streams a stored file (supports video seeking). Same scope rules as the pages. */
    public function media(Request $request, Pu $pu, string $kind, int $index = 0)
    {
        $user = $request->user();
        abort_unless($user->checkPermissionTo('pu-tasks.manage') || $user->checkPermissionTo('pu-tasks.view'), 403);
        abort_unless(Pu::query()->visibleTo($user)->whereKey($pu->id)->exists(), 404);

        $submission = $pu->submissions()->where('office', $this->office($request)->value)->first();
        $path = match ($kind) {
            'video' => $submission?->video_path,
            'sheet' => $submission?->sheets[$index]['path'] ?? null,
            default => null,
        };
        abort_unless($path && Storage::disk(self::DISK)->exists($path), 404);

        return response()->file(Storage::disk(self::DISK)->path($path), ['Cache-Control' => 'private, max-age=3600']);
    }

    /* ------------------------------------------------------- management view */

    public function reports(Request $request)
    {
        $user = $request->user();
        abort_unless($user->checkPermissionTo('pu-tasks.view'), 403);

        $office = $this->office($request);
        $base = fn () => Pu::query()->visibleTo($user);

        $pus = $base()->with(['ward.lga', 'official', 'submission' => $this->forOffice($office), 'submission.submitter'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('pus.name', 'like', "%{$s}%")->orWhere('pus.code', 'like', "%{$s}%")))
            ->when($request->status === 'officials', fn ($q) => $q->whereDoesntHave('official'))
            ->when($request->status === 'result', fn ($q) => $q->resultIncomplete($office->value))
            ->when($request->status === 'complete', fn ($q) => $q->whereHas('official')->resultComplete($office->value))
            ->orderBy('pus.name')
            ->paginate(15)->withQueryString();

        return Inertia::render('dashboard/pu-reports/Index', [
            'pus' => [
                'data' => collect($pus->items())->map(fn (Pu $pu) => $this->summary($pu) + [
                    'lga' => $pu->ward?->lga?->name,
                    'submitted_by' => $pu->submission?->submitter
                        ? trim($pu->submission->submitter->first_name.' '.$pu->submission->submitter->last_name) : null,
                    'submitted_at' => $pu->submission?->updated_at?->toIso8601String(),
                    'official' => $pu->official?->only(['po_name', 'apo1_name', 'apo2_name', 'security_name']),
                    'submission' => $this->submissionPayload($pu->submission),
                ])->values(),
                'meta' => [
                    'current_page' => $pus->currentPage(), 'last_page' => $pus->lastPage(),
                    'from' => $pus->firstItem(), 'to' => $pus->lastItem(),
                    'per_page' => $pus->perPage(), 'total' => $pus->total(),
                ],
            ],
            'stats' => [
                'total' => $base()->count(),
                'officials' => $base()->whereHas('official')->count(),
                'results' => $base()->resultComplete($office->value)->count(),
            ],
            'filters' => $request->only(['search', 'status']),
            'office' => $office->value,
            'offices' => $this->officeOptions(),
        ]);
    }

    /* -------------------------------------------------------------- helpers */

    /** Office the task belongs to (?office=governor ...). Defaults to governor. */
    private function office(Request $request): AspirantOffice
    {
        return AspirantOffice::tryFrom((string) ($request->query('office') ?? $request->input('office'))) ?? AspirantOffice::GOVERNOR;
    }

    private function forOffice(AspirantOffice $office): \Closure
    {
        return fn ($q) => $q->where('office', $office->value);
    }

    private function officeLabel(AspirantOffice $office): string
    {
        return ucwords(str_replace('_', ' ', $office->value));
    }

    private function officeOptions(): array
    {
        return collect(AspirantOffice::cases())->map(fn ($o) => ['value' => $o->value, 'label' => $this->officeLabel($o)])->all();
    }

    private function agent(Request $request): User
    {
        $user = $request->user();
        abort_unless($user->checkPermissionTo('pu-tasks.manage'), 403);

        return $user;
    }

    private function authorizePu(Request $request, Pu $pu): void
    {
        $user = $this->agent($request);
        abort_unless(Pu::query()->visibleTo($user)->whereKey($pu->id)->exists(), 404);
    }

    private function forget(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    private function puInfo(Pu $pu): array
    {
        return ['id' => $pu->id, 'name' => $pu->name, 'code' => $pu->code, 'ward' => $pu->ward?->name, 'lga' => $pu->ward?->lga?->name];
    }

    private function hasResult(?PuResultSubmission $s): bool
    {
        return $s && $s->video_path && count($s->sheets ?? []) > 0;
    }

    private function summary(Pu $pu): array
    {
        $s = $pu->submission;

        return [
            'id' => $pu->id, 'name' => $pu->name, 'code' => $pu->code, 'ward' => $pu->ward?->name,
            'officials_done' => (bool) $pu->official,
            'has_video' => (bool) $s?->video_path,
            'sheet_count' => count($s?->sheets ?? []),
            'result_done' => $this->hasResult($s),
        ];
    }

    private function submissionPayload(?PuResultSubmission $s): ?array
    {
        if (! $s) {
            return null;
        }

        return [
            'video' => $s->video_path ? ['url' => route('pu-media', [$s->pu_id, 'video', 'office' => $s->office->value]), 'name' => $s->video_name] : null,
            'sheets' => collect($s->sheets ?? [])->values()->map(fn ($f, $i) => [
                'index' => $i, 'name' => $f['name'] ?? "Sheet ".($i + 1), 'url' => route('pu-media', [$s->pu_id, 'sheet', $i, 'office' => $s->office->value]),
            ])->all(),
            'remarks' => $s->remarks,
            'updated_at' => $s->updated_at?->toIso8601String(),
            'submitted_by' => $s->submitter ? trim($s->submitter->first_name.' '.$s->submitter->last_name) : null,
        ];
    }
}
