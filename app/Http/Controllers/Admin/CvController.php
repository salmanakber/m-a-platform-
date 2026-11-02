<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesCvStudio;
use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\ExpertCv;
use App\Services\Cv\CvGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CvController extends Controller
{
    use HandlesCvStudio;

    public function __construct(private CvGeneratorService $cvs)
    {
    }

    protected function cvService(): CvGeneratorService
    {
        return $this->cvs;
    }

    public function index(Request $request, Expert $expert): View
    {
        $boot = $this->studioBoot($expert, $expert->cvs()->latest()->get());
        $boot['expert']['profile_edit_url'] = route('admin.experts.edit', $expert);
        $boot['urls'] = [
            'store' => route('admin.experts.cv.store', $expert),
            'update' => route('admin.experts.cv.update', [$expert, '__ID__']),
            'generate' => route('admin.experts.cv.generate-ai', [$expert, '__ID__']),
            'finalize' => route('admin.experts.cv.finalize', [$expert, '__ID__']),
            'preview' => route('admin.experts.cv.preview', [$expert, '__ID__']),
            'destroy' => route('admin.experts.cv.destroy', [$expert, '__ID__']),
            'show' => route('admin.experts.cv.show', [$expert, '__ID__']),
            'suggest_template' => route('admin.experts.cv.suggest-template', $expert),
            'invent_design' => route('admin.experts.cv.invent-design', $expert),
            'live_preview' => route('admin.experts.cv.live-preview', $expert),
            'pdf' => route('admin.experts.cv.pdf', [$expert, '__ID__']),
        ];
        $boot['initial_cv_id'] = $request->integer('cv') ?: null;

        return view('admin.experts.cv.index', [
            'expert' => $expert,
            'boot' => $boot,
        ]);
    }

    public function show(Expert $expert, ExpertCv $cv): JsonResponse
    {
        abort_if((int) $cv->expert_id !== (int) $expert->id, 404);

        return $this->jsonCv($cv);
    }

    public function store(Request $request, Expert $expert): JsonResponse|RedirectResponse
    {
        $payload = $this->parsePayload($request, $expert);
        unset($payload['_notes']);

        $template = $request->input('template_key', 'classic');
        if ($request->boolean('use_ai_template')) {
            try {
                $template = $this->cvs->suggestTemplate($expert, $payload);
            } catch (\Throwable) {
            }
        }

        $cv = $this->cvs->createDraft($expert, $template, $payload);
        if ($request->filled('title')) {
            $cv->update(['title' => $request->input('title')]);
            $cv = $cv->fresh();
        }

        if ($request->expectsJson() || $request->ajax()) {
            return $this->jsonCv($cv, 'Entwurf erstellt.', 201);
        }

        return redirect()->route('admin.experts.cv.index', [$expert, 'cv' => $cv->id]);
    }

    public function suggestTemplate(Request $request, Expert $expert): JsonResponse
    {
        $payload = $request->input('payload', []);
        if (! is_array($payload)) {
            $payload = [];
        }

        try {
            $key = $this->cvs->suggestTemplate($expert, $payload);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'template_key' => $key,
            'template_label' => CvGeneratorService::TEMPLATES[$key] ?? $key,
        ]);
    }

    public function inventDesign(Request $request, Expert $expert): JsonResponse
    {
        $payload = $request->input('payload', []);
        if (! is_array($payload)) {
            $payload = [];
        }

        try {
            $design = $this->cvs->inventDesign($expert, $payload);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'template_key' => $design['template_key'],
            'template_label' => CvGeneratorService::TEMPLATES[$design['template_key']] ?? $design['template_key'],
            'accent_color' => $design['accent_color'],
            'layout' => $design['layout'],
            'label' => $design['label'] ?? 'KI-Design',
        ]);
    }

    public function livePreview(Request $request, Expert $expert): Response
    {
        $payload = $this->parsePayload($request, $expert);
        unset($payload['_notes']);
        $template = $request->input('template_key', 'classic');

        return response($this->cvs->renderPreviewHtml($expert, (string) $template, $payload))
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function edit(Expert $expert, ExpertCv $cv): RedirectResponse
    {
        abort_if((int) $cv->expert_id !== (int) $expert->id, 404);

        return redirect()->route('admin.experts.cv.index', [$expert, 'cv' => $cv->id]);
    }

    public function update(Request $request, Expert $expert, ExpertCv $cv): JsonResponse|RedirectResponse
    {
        abort_if((int) $cv->expert_id !== (int) $expert->id, 404);
        $payload = $this->parsePayload($request, $expert);
        unset($payload['_notes']);
        $cv = $this->cvs->updateDraft($cv, $payload, $request->input('template_key'), $request->input('title'));

        if ($request->expectsJson() || $request->ajax()) {
            return $this->jsonCv($cv, 'Gespeichert.');
        }

        return back()->with('success', 'CV gespeichert.');
    }

    public function generateAi(Request $request, Expert $expert, ExpertCv $cv): JsonResponse|RedirectResponse
    {
        abort_if((int) $cv->expert_id !== (int) $expert->id, 404);

        $validated = $request->validate([
            'mode' => ['nullable', 'in:full,field'],
            'field' => ['nullable', 'in:summary,services,experience,education,skills'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'template_key' => ['nullable', 'in:'.implode(',', array_keys(CvGeneratorService::TEMPLATES))],
            'invent_design' => ['nullable', 'boolean'],
            'payload' => ['nullable', 'array'],
        ]);

        $mode = $validated['mode'] ?? (($validated['field'] ?? null) ? 'field' : 'full');

        try {
            if ($mode === 'full') {
                $basePayload = is_array($validated['payload'] ?? null)
                    ? array_merge($cv->payload ?? [], $validated['payload'])
                    : ($cv->payload ?? []);
                $result = $this->cvs->generateFullCv($expert, [
                    'notes' => $validated['notes'] ?? '',
                    'payload' => $basePayload,
                    'template_key' => $validated['template_key'] ?? $cv->template_key,
                    'invent_design' => (bool) ($validated['invent_design'] ?? false),
                ]);
                $cv = $this->cvs->updateDraft(
                    $cv,
                    $result['payload'],
                    $result['template_key'],
                    $result['title']
                );
                $preview = mb_substr((string) ($result['payload']['summary'] ?? ''), 0, 280);
                $field = 'full';
            } else {
                if (empty($validated['field'])) {
                    throw new \InvalidArgumentException('Feld fehlt.');
                }
                $text = $this->cvs->generateField($expert, $validated['field'], [
                    'notes' => $validated['notes'] ?? '',
                    'payload' => $cv->payload ?? [],
                ]);
                $payload = $this->cvs->mergeGeneratedField($cv->payload ?? [], $validated['field'], $text);
                $cv = $this->cvs->updateDraft($cv, $payload);
                $preview = mb_substr($text, 0, 280);
                $field = $validated['field'];
            }
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage(), 'field' => $validated['field'] ?? 'full'], 422);
            }

            return back()->with('error', 'KI-Generierung fehlgeschlagen: '.$e->getMessage());
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $mode === 'full' ? 'Lebenslauf erzeugt.' : 'Abschnitt erzeugt.',
                'field' => $field,
                'preview' => $preview,
                'cv' => $this->cvs->toStudioArray($cv),
            ]);
        }

        return back()->with('success', 'KI-Inhalt eingefügt.');
    }

    public function preview(Request $request, Expert $expert, ExpertCv $cv): Response
    {
        abort_if((int) $cv->expert_id !== (int) $expert->id, 404);

        return response($this->cvs->renderHtml($cv))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function pdf(Expert $expert, ExpertCv $cv)
    {
        abort_if((int) $cv->expert_id !== (int) $expert->id, 404);

        if ($this->cvs->canGeneratePdf()) {
            try {
                $binary = $this->cvs->pdfBinary($cv);
                $this->cvs->syncArtifacts($cv);
                $filename = 'Lebenslauf-'.preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($cv->title ?: 'CV')).'.pdf';

                return response($binary, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                ]);
            } catch (\Throwable) {
                // Fall through.
            }
        }

        return response($this->cvs->renderPrintHtml($cv, true))
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function finalize(Expert $expert, ExpertCv $cv): JsonResponse|RedirectResponse
    {
        abort_if((int) $cv->expert_id !== (int) $expert->id, 404);
        $cv = $this->cvs->finalize($cv);

        if (request()->expectsJson() || request()->ajax()) {
            return $this->jsonCv($cv, 'CV finalisiert.');
        }

        return back()->with('success', 'CV finalisiert.');
    }

    public function destroy(Expert $expert, ExpertCv $cv): JsonResponse|RedirectResponse
    {
        abort_if((int) $cv->expert_id !== (int) $expert->id, 404);
        $cv->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['ok' => true, 'message' => 'CV gelöscht.']);
        }

        return redirect()->route('admin.experts.cv.index', $expert)->with('success', 'CV gelöscht.');
    }
}
