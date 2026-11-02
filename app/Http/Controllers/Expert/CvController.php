<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Controller;
use App\Models\ExpertCv;
use App\Services\Cv\CvGeneratorService;
use App\Support\TextLines;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CvController extends Controller
{
    public function __construct(private CvGeneratorService $cvs)
    {
    }

    public function index(): View
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null, 403);

        return view('expert.cv.index', [
            'expert' => $expert,
            'cvs' => $expert->cvs()->latest()->get(),
            'templates' => CvGeneratorService::TEMPLATES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null, 403);

        $validated = $request->validate([
            'template_key' => ['nullable', 'in:'.implode(',', array_keys(CvGeneratorService::TEMPLATES))],
            'use_ai_template' => ['nullable', 'boolean'],
        ]);

        $template = $validated['template_key'] ?? 'classic';
        if ($request->boolean('use_ai_template')) {
            try {
                $template = $this->cvs->suggestTemplate($expert);
            } catch (\Throwable) {
                // keep classic
            }
        }

        $cv = $this->cvs->createDraft($expert, $template);

        return redirect()
            ->route('expert.cv.edit', $cv)
            ->with('success', 'CV-Entwurf aus Profil erstellt. Felder prüfen und speichern.');
    }

    public function edit(ExpertCv $cv): View
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $cv->expert_id !== (int) $expert->id, 403);

        return view('expert.cv.edit', [
            'expert' => $expert,
            'cv' => $cv,
            'payload' => $cv->payload ?? [],
            'templates' => CvGeneratorService::TEMPLATES,
        ]);
    }

    public function update(Request $request, ExpertCv $cv): RedirectResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $cv->expert_id !== (int) $expert->id, 403);

        $payload = $this->validatedPayload($request);
        $this->cvs->updateDraft(
            $cv,
            $payload,
            $request->input('template_key'),
            $request->input('title')
        );

        return back()->with('success', 'CV gespeichert.');
    }

    public function generateAi(Request $request, ExpertCv $cv): RedirectResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $cv->expert_id !== (int) $expert->id, 403);

        $validated = $request->validate([
            'field' => ['required', 'in:summary,services,experience,education,skills'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $text = $this->cvs->generateField($expert, $validated['field'], [
                'notes' => $validated['notes'] ?? '',
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'KI-Generierung fehlgeschlagen: '.$e->getMessage());
        }

        $payload = $cv->payload ?? [];
        if (in_array($validated['field'], ['experience_areas', 'skills', 'languages'], true)) {
            $payload[$validated['field']] = TextLines::fromInput(str_replace(',', "\n", $text));
        } elseif (in_array($validated['field'], ['experience', 'education'], true)) {
            $payload[$validated['field']] = TextLines::fromInput($text);
        } else {
            $payload[$validated['field']] = $text;
        }

        $this->cvs->updateDraft($cv, $payload);

        return back()->with('success', 'KI-Inhalt für «'.$validated['field'].'» eingefügt — bitte prüfen.');
    }

    public function preview(ExpertCv $cv): Response
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $cv->expert_id !== (int) $expert->id, 403);

        return response($this->cvs->renderHtml($cv))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function finalize(ExpertCv $cv): RedirectResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $cv->expert_id !== (int) $expert->id, 403);

        $this->cvs->finalize($cv);

        return back()->with('success', 'CV finalisiert. Sie können die HTML-Version öffnen/drucken (PDF über den Browser).');
    }

    public function destroy(ExpertCv $cv): RedirectResponse
    {
        $expert = Auth::user()?->resolvePortalExpert();
        abort_if($expert === null || (int) $cv->expert_id !== (int) $expert->id, 403);
        $cv->delete();

        return redirect()->route('expert.cv.index')->with('success', 'CV gelöscht.');
    }

    /** @return array<string, mixed> */
    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'template_key' => ['nullable', 'in:'.implode(',', array_keys(CvGeneratorService::TEMPLATES))],
            'full_name' => ['nullable', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'website' => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:8000'],
            'services' => ['nullable', 'string', 'max:8000'],
            'equity_capital' => ['nullable', 'string', 'max:120'],
            'experience_areas' => ['nullable', 'string', 'max:5000'],
            'diplomas' => ['nullable', 'string', 'max:5000'],
            'criteria' => ['nullable', 'string', 'max:5000'],
            'experience' => ['nullable', 'string', 'max:12000'],
            'education' => ['nullable', 'string', 'max:8000'],
            'skills' => ['nullable', 'string', 'max:4000'],
            'languages' => ['nullable', 'string', 'max:2000'],
        ]);

        return [
            'full_name' => $validated['full_name'] ?? '',
            'headline' => $validated['headline'] ?? '',
            'company' => $validated['company'] ?? '',
            'email' => $validated['email'] ?? '',
            'phone' => $validated['phone'] ?? '',
            'website' => $validated['website'] ?? '',
            'location' => $validated['location'] ?? '',
            'summary' => $validated['summary'] ?? '',
            'services' => $validated['services'] ?? '',
            'equity_capital' => $validated['equity_capital'] ?? '',
            'experience_areas' => TextLines::fromInput($validated['experience_areas'] ?? null),
            'diplomas' => TextLines::fromInput($validated['diplomas'] ?? null),
            'criteria' => TextLines::fromInput($validated['criteria'] ?? null),
            'experience' => TextLines::fromInput($validated['experience'] ?? null),
            'education' => TextLines::fromInput($validated['education'] ?? null),
            'skills' => TextLines::fromInput($validated['skills'] ?? null),
            'languages' => TextLines::fromInput($validated['languages'] ?? null),
            'photo_path' => $request->user()?->resolvePortalExpert()?->contact_photo_path,
        ];
    }
}
