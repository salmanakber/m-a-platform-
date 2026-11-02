<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Concerns\HandlesCvStudio;
use App\Http\Controllers\Controller;
use App\Models\ExpertCv;
use App\Services\Cv\CvGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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

    public function index(Request $request): View
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null, 403);

        $boot = $this->studioBoot($expert, $expert->cvs()->latest()->get());
        $boot['expert']['profile_edit_url'] = route('expert.profile.edit');
        $boot['urls'] = [
            'store' => route('expert.cv.store'),
            'update' => route('expert.cv.update', ['cv' => '__ID__']),
            'generate' => route('expert.cv.generate-ai', ['cv' => '__ID__']),
            'finalize' => route('expert.cv.finalize', ['cv' => '__ID__']),
            'preview' => route('expert.cv.preview', ['cv' => '__ID__']),
            'destroy' => route('expert.cv.destroy', ['cv' => '__ID__']),
            'show' => route('expert.cv.show', ['cv' => '__ID__']),
            'suggest_template' => route('expert.cv.suggest-template'),
            'invent_design' => route('expert.cv.invent-design'),
            'live_preview' => route('expert.cv.live-preview'),
        ];
        $boot['initial_cv_id'] = $request->integer('cv') ?: null;

        return view('expert.cv.index', [
            'expert' => $expert,
            'boot' => $boot,
        ]);
    }

    public function show(ExpertCv $cv): JsonResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $cv->expert_id !== (int) $expert->id, 403);

        return $this->jsonCv($cv);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null, 403);

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
            return $this->jsonCv($cv, 'Entwurf aus Ihren Angaben erstellt.', 201);
        }

        return redirect()->route('expert.cv.index', ['cv' => $cv->id]);
    }

    public function suggestTemplate(Request $request): JsonResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null, 403);

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

    public function inventDesign(Request $request): JsonResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null, 403);

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
        ]);
    }

    public function livePreview(Request $request): Response
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null, 403);

        $payload = $this->parsePayload($request, $expert);
        unset($payload['_notes']);
        $template = $request->input('template_key', 'classic');

        return response($this->cvs->renderPreviewHtml($expert, (string) $template, $payload))
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function edit(ExpertCv $cv): RedirectResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $cv->expert_id !== (int) $expert->id, 403);

        return redirect()->route('expert.cv.index', ['cv' => $cv->id]);
    }

    public function update(Request $request, ExpertCv $cv): JsonResponse|RedirectResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $cv->expert_id !== (int) $expert->id, 403);

        $payload = $this->parsePayload($request, $expert);
        unset($payload['_notes']);
        $cv = $this->cvs->updateDraft($cv, $payload, $request->input('template_key'), $request->input('title'));

        if ($request->expectsJson() || $request->ajax()) {
            return $this->jsonCv($cv, 'Gespeichert.');
        }

        return back()->with('success', 'CV gespeichert.');
    }

    public function generateAi(Request $request, ExpertCv $cv): JsonResponse|RedirectResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $cv->expert_id !== (int) $expert->id, 403);

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

    public function preview(Request $request, ExpertCv $cv): Response
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $cv->expert_id !== (int) $expert->id, 403);

        $html = $this->cvs->renderHtml($cv);
        if ($request->boolean('print')) {
            $html = preg_replace(
                '/<\/body>/i',
                '<script>window.addEventListener("load",function(){setTimeout(function(){window.print()},200)});</script></body>',
                $html,
                1
            ) ?: $html;
        }

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function finalize(ExpertCv $cv): JsonResponse|RedirectResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $cv->expert_id !== (int) $expert->id, 403);

        $cv = $this->cvs->finalize($cv);

        if (request()->expectsJson() || request()->ajax()) {
            return $this->jsonCv($cv, 'CV finalisiert — bereit zum Drucken / Teilen.');
        }

        return back()->with('success', 'CV finalisiert.');
    }

    public function destroy(ExpertCv $cv): JsonResponse|RedirectResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $cv->expert_id !== (int) $expert->id, 403);
        $cv->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['ok' => true, 'message' => 'CV gelöscht.']);
        }

        return redirect()->route('expert.cv.index')->with('success', 'CV gelöscht.');
    }
}
