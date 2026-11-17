<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AspirantOffice;
use App\Http\Controllers\Controller;
use App\Models\Pu;
use App\Models\PuOfficial;
use App\Models\PuResultSubmission;
use App\Models\PuTaskAudit;
use App\Models\User;
use App\Support\Jurisdiction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Throwable;
use RuntimeException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Party-agent field tasks for a polling unit:
 *  1. capture the officials (PO, APO1, APO2, security),
 *  2. upload the counting video,
 *  3. upload result-sheet photos,
 *  4. enter party figures.
 *
 * A PU agent works on his own PU; a ward agent works on every PU in his ward.
 * Scope is enforced by Pu::visibleTo (Jurisdiction), never by the URL.
 */
class PuTaskController extends Controller
{
    private const DISK = 'local';          // private disk, served only through media()
    private const MAX_SHEETS = 8;

    /* ---------------------------------------------------------------- agent */

    /**
     * PU agent: straight to his own unit (nothing to browse).
     * Ward agent: the list of units in his ward, with a few simple filters.
     */
    public function index(Request $request)
    {
        $user = $this->agent($request);
        $office = $this->office($request);

        if (Jurisdiction::for($user)->level === 'pu') {
            $pu = Pu::query()->visibleTo($user)->first();

            return $pu
                ? redirect()->route('portal.pus.show', ['pu' => $pu->id, 'office' => $office->value])
                : redirect()->route('portal.home');
        }

        $base = fn () => Pu::query()->visibleTo($user);
        $status = in_array($request->status, ['new', 'progress', 'done'], true) ? $request->status : '';

        $pus = $base()->with([
            'ward', 'official', 'submission' => $this->forOffice($office),
            'results' => fn ($q) => $q->where('office', $office->value)->whereHas('election', fn ($e) => $e->where('is_active', true)),
        ])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('pus.name', 'like', "%{$s}%")->orWhere('pus.code', 'like', "%{$s}%")))
            ->when($status === 'new', fn ($q) => $q->taskNotStarted($office->value))
            ->when($status === 'done', fn ($q) => $q->taskDone($office->value))
            ->when($status === 'progress', fn ($q) => $q->whereNot(fn ($q) => $q->taskNotStarted($office->value))
                ->whereNot(fn ($q) => $q->taskDone($office->value)))
            ->orderBy('pus.name')
            ->paginate(12)->withQueryString();

        $total = $base()->count();
        $done = $base()->taskDone($office->value)->count();
        $new = $base()->taskNotStarted($office->value)->count();

        return Inertia::render('portal/pus/Index', [
            'scopeName' => Jurisdiction::for($user)->name(),
            'pus' => [
                'data' => collect($pus->items())->map(fn (Pu $pu) => $this->summary($pu))->values(),
                'meta' => [
                    'current_page' => $pus->currentPage(), 'last_page' => $pus->lastPage(),
                    'from' => $pus->firstItem(), 'to' => $pus->lastItem(),
                    'per_page' => $pus->perPage(), 'total' => $pus->total(),
                ],
            ],
            'counts' => ['all' => $total, 'new' => $new, 'progress' => $total - $new - $done, 'done' => $done],
            'filters' => ['search' => $request->search, 'status' => $status],
            'office' => $office->value,
            'offices' => $this->officeOptions(),
        ]);
    }

    /** One polling unit: the four required field tasks. */
    public function show(Request $request, Pu $pu)
    {
        $this->authorizePu($request, $pu);
        $office = $this->office($request);
        $pu->load([
            'ward.lga', 'official', 'submission' => $this->forOffice($office),
            'results' => fn ($q) => $q->where('office', $office->value)->whereHas('election', fn ($e) => $e->where('is_active', true)),
        ]);

        return Inertia::render('portal/pus/Show', [
            'unit' => $this->summary($pu) + ['lga' => $pu->ward?->lga?->name],
            'office' => $office->value,
            'offices' => $this->officeOptions(),
            'canGoBack' => Jurisdiction::for($request->user())->level !== 'pu',
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
            'office' => $this->office($request)->value,
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
        ], ['required' => 'Please write this name.']);

        $existing = PuOfficial::query()->where('pu_id', $pu->id)->first();
        DB::transaction(function () use ($pu, $data, $request, $existing) {
            PuOfficial::updateOrCreate(['pu_id' => $pu->id], $data + ['updated_by' => $request->user()->id]);
            PuTaskAudit::create([
                'pu_id' => $pu->id,
                'action' => $existing ? 'officials_updated' : 'officials_recorded',
                'details' => [
                    'before' => $existing?->only(['po_name', 'apo1_name', 'apo2_name', 'security_name']),
                    'after' => $data,
                ],
                'performed_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('portal.pus.show', ['pu' => $pu->id, 'office' => $this->office($request)->value])
            ->with('success', 'Names saved.');
    }

    /** Result sheet photos page. */
    public function sheet(Request $request, Pu $pu)
    {
        return $this->evidencePage($request, $pu, 'portal/pus/Sheet');
    }

    /** Counting video page. */
    public function video(Request $request, Pu $pu)
    {
        return $this->evidencePage($request, $pu, 'portal/pus/Video');
    }

    public function saveSheet(Request $request, Pu $pu)
    {
        $this->authorizePu($request, $pu);
        $office = $this->office($request);
        $submission = $this->submissionFor($pu, $office);
        $room = max(0, self::MAX_SHEETS - count($submission->sheets ?? []));

        $request->validate([
            'sheets' => ['required', 'array', 'min:1', 'max:'.$room],
            'sheets.*' => ['image', 'max:10240'],
        ], [
            'sheets.required' => 'Take a photo of the result sheet first.',
            'sheets.max' => 'Too many photos. You can keep '.self::MAX_SHEETS.' at most. Remove one first.',
            'sheets.*.image' => 'This file is not a photo.',
            'sheets.*.max' => 'A photo is too big (limit 10 MB).',
        ]);

        $sheets = $submission->sheets ?? [];
        $storedPaths = [];

        try {
            foreach ($request->file('sheets') as $photo) {
                $path = $photo->storeAs("pu-evidence/{$pu->id}", 'sheet-'.Str::uuid().'.'.($photo->extension() ?: 'jpg'), self::DISK);
                if (! $path) {
                    throw new RuntimeException('The result-sheet photo could not be saved. Please try again.');
                }
                $storedPaths[] = $path;
                $sheets[] = [
                    'path' => $path,
                    'name' => Str::limit($photo->getClientOriginalName(), 120, ''),
                ];
            }

            DB::transaction(function () use ($submission, $sheets, $request, $pu, $office, $storedPaths) {
                $submission->sheets = array_values($sheets);
                $submission->submitted_by = $request->user()->id;
                $submission->save();
                PuTaskAudit::create([
                    'pu_id' => $pu->id,
                    'office' => $office->value,
                    'action' => 'result_sheets_uploaded',
                    'details' => ['files' => count($storedPaths), 'total_sheets' => count($sheets)],
                    'performed_by' => $request->user()->id,
                ]);
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                $this->forget($path);
            }

            throw $exception;
        }

        return redirect()->route('portal.pus.show', ['pu' => $pu->id, 'office' => $office->value])->with('success', 'Result sheet photo saved.');
    }

    public function saveVideo(Request $request, Pu $pu)
    {
        $this->authorizePu($request, $pu);
        $office = $this->office($request);

        $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm,video/3gpp,video/x-matroska', 'max:153600'],
        ], [
            'video.required' => 'Record or choose a video first.',
            'video.max' => 'The video is too big (limit 150 MB). Record a shorter one.',
            'video.mimetypes' => 'This file is not a video.',
            'video.uploaded' => 'The video could not be uploaded. Check your network and try again.',
        ]);

        $submission = $this->submissionFor($pu, $office);
        $previousPath = $submission->video_path;
        $file = $request->file('video');
        $newPath = $file->storeAs("pu-evidence/{$pu->id}", 'video-'.Str::uuid().'.'.($file->extension() ?: 'mp4'), self::DISK);
        if (! $newPath) {
            throw new RuntimeException('The counting video could not be saved. Please try again.');
        }

        try {
            DB::transaction(function () use ($submission, $newPath, $file, $request, $pu, $office) {
                $submission->video_path = $newPath;
                $submission->video_name = Str::limit($file->getClientOriginalName(), 120, '');
                $submission->submitted_by = $request->user()->id;
                $submission->save();
                PuTaskAudit::create([
                    'pu_id' => $pu->id,
                    'office' => $office->value,
                    'action' => 'counting_video_uploaded',
                    'details' => ['file' => $submission->video_name],
                    'performed_by' => $request->user()->id,
                ]);
            });
        } catch (Throwable $exception) {
            $this->forget($newPath);
            throw $exception;
        }

        // Keep the previous evidence until the replacement has been committed.
        if ($previousPath && $previousPath !== $newPath) {
            $this->forget($previousPath);
        }

        return redirect()->route('portal.pus.show', ['pu' => $pu->id, 'office' => $office->value])->with('success', 'Video saved.');
    }

    public function deleteFile(Request $request, Pu $pu)
    {
        $this->authorizePu($request, $pu);
        $request->validate(['kind' => ['required', Rule::in(['video', 'sheet'])], 'index' => ['nullable', 'integer', 'min:0']]);

        $submission = $pu->submissions()->where('office', $this->office($request)->value)->first();
        abort_unless($submission, 404);

        $pathsToDelete = [];
        $deletedDetails = [];

        if ($request->kind === 'video') {
            if ($submission->video_path) {
                $pathsToDelete[] = $submission->video_path;
            }
            $deletedDetails = ['file' => $submission->video_name];
            $submission->video_path = $submission->video_name = null;
        } else {
            $sheets = $submission->sheets ?? [];
            $i = $request->integer('index');
            abort_unless(isset($sheets[$i]), 404);
            if (! empty($sheets[$i]['path'])) {
                $pathsToDelete[] = $sheets[$i]['path'];
            }
            $deletedDetails = ['file' => $sheets[$i]['name'] ?? null, 'index' => $i];
            unset($sheets[$i]);
            $submission->sheets = array_values($sheets);
        }

        DB::transaction(function () use ($submission, $pu, $request, $deletedDetails) {
            $submission->save();
            PuTaskAudit::create([
                'pu_id' => $pu->id,
                'office' => $submission->office->value,
                'action' => $request->kind === 'video' ? 'counting_video_deleted' : 'result_sheet_deleted',
                'details' => $deletedDetails,
                'performed_by' => $request->user()->id,
            ]);
        });

        // Remove the private file only after the database no longer references it.
        foreach ($pathsToDelete as $path) {
            $this->forget($path);
        }

        return back()->with('success', 'Removed.');
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

        return response()->file(Storage::disk(self::DISK)->path($path), [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /* ------------------------------------------------------- management view */

    public function reports(Request $request)
    {
        $user = $request->user();
        abort_unless($user->checkPermissionTo('pu-tasks.view'), 403);

        $office = $this->office($request);
        $base = fn () => Pu::query()->visibleTo($user);

        $pus = $base()->with([
            'ward.lga', 'official', 'submission' => $this->forOffice($office), 'submission.submitter',
            'results' => fn ($q) => $q->where('office', $office->value)->whereHas('election', fn ($e) => $e->where('is_active', true)),
            'taskAudits' => fn ($q) => $q->with('performer')
                ->where(fn ($q) => $q->whereNull('office')->orWhere('office', $office->value))
                ->latest()->limit(5),
        ])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('pus.name', 'like', "%{$s}%")->orWhere('pus.code', 'like', "%{$s}%")))
            ->when($request->status === 'officials', fn ($q) => $q->whereDoesntHave('official'))
            ->when($request->status === 'result', fn ($q) => $q->resultIncomplete($office->value))
            ->when($request->status === 'complete', fn ($q) => $q->taskDone($office->value))
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
                    'activity' => $pu->taskAudits->map(fn (PuTaskAudit $audit) => [
                        'action' => $audit->action,
                        'performed_by' => $audit->performer
                            ? trim($audit->performer->first_name.' '.$audit->performer->last_name) : null,
                        'created_at' => $audit->created_at?->toIso8601String(),
                        'details' => $audit->details,
                    ])->values(),
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

    private function evidencePage(Request $request, Pu $pu, string $component)
    {
        $this->authorizePu($request, $pu);
        $office = $this->office($request);
        $pu->load(['ward.lga', 'submission' => $this->forOffice($office), 'submission.submitter']);

        return Inertia::render($component, [
            'pu' => $this->puInfo($pu),
            'office' => $office->value,
            'submission' => $this->submissionPayload($pu->submission),
            'limits' => ['video_mb' => 150, 'sheet_mb' => 10, 'max_sheets' => self::MAX_SHEETS],
        ]);
    }

    private function submissionFor(Pu $pu, AspirantOffice $office): PuResultSubmission
    {
        return $pu->submissions()->where('office', $office->value)->first()
            ?? new PuResultSubmission(['pu_id' => $pu->id, 'office' => $office->value]);
    }

    /** Office the task belongs to (?office=governor ...). Defaults to governor. */
    private function office(Request $request): AspirantOffice
    {
        $active = (array) config('elections.active_offices', ['governor']);
        $asked = AspirantOffice::tryFrom((string) ($request->query('office') ?? $request->input('office')));

        return ($asked && in_array($asked->value, $active, true)) ? $asked : (AspirantOffice::tryFrom($active[0] ?? '') ?? AspirantOffice::GOVERNOR);
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
        $active = (array) config('elections.active_offices', ['governor']);

        return collect(AspirantOffice::cases())->filter(fn ($o) => in_array($o->value, $active, true))
            ->map(fn ($o) => ['value' => $o->value, 'label' => $this->officeLabel($o)])->values()->all();
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
        $officials = (bool) $pu->official;
        $video = (bool) $s?->video_path;
        $sheets = count($s?->sheets ?? []);
        $figures = $pu->results->isNotEmpty();

        return [
            'id' => $pu->id, 'name' => $pu->name, 'code' => $pu->code, 'ward' => $pu->ward?->name,
            'officials_done' => $officials,
            'has_video' => $video,
            'sheet_count' => $sheets,
            'has_figures' => $figures,
            'result_done' => $this->hasResult($s),
            'steps_done' => (int) $officials + (int) ($sheets > 0) + (int) $video + (int) $figures,
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
