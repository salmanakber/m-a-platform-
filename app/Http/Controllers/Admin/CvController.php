<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\ExpertCv;
use App\Services\Cv\CvGeneratorService;
use App\Support\TextLines;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CvController extends Controller
{
    public function __construct(private CvGeneratorService $cvs)
    {
    }

    public function index(Expert $expert): View
    {
        return view('admin.experts.cv.index', [
            'expert' => $expert,
            'cvs' => $expert->cvs()->latest()->get(),
            'templates' => CvGeneratorService::TEMPLATES,
        ]);
    }

    public function store(Request $request, Expert $expert): RedirectResponse
    {
        $validated = $request->validate([
            'template_key' => ['nullable', 'in:'.implode(',', array_keys(CvGeneratorService::TEMPLATES))],
            'use_ai_template' => ['nullable', 'boolean'],
        ]);

        $template = $validated['template_key'] ?? 'classic';
        if ($request->boolean('use_ai_template')) {
            try {
                $template = $this->cvs->suggestTemplate($expert);
            } catch (\Throwable) {
            }
        }

        $cv = $this->cvs->createDraft($expert, $template);

        return redirect()
            ->route('admin.experts.cv.edit', [$expert, $cv])
            ->with('success', 'CV-Entwurf erstellt.');
    }

    public function edit(Expert $expert, ExpertCv $cv): View
    {
        abort_if((int) $cv->expert_id !== (int) $expert->id, 404);

        return view('admin.experts.cv.edit', [
            'expert' => $expert,
            'cv' => $cv,
            'payload' => $cv->payload ?? [],
            'templates' => CvGeneratorService::TEMPLATES,
        ]);
    }

    public function update(Request $request, Expert $expert, ExpertCv $cv): RedirectResponse
    {
        abort_if((int) $cv->expert_id !== (int) $expert->id, 404);
        $payload = $this->validatedPayload($request, $expert);
        $this->cvs->updateDraft($cv, $payload, $request->input('template_key'), $request->input('title'));

        return back()->with('success', 'CV gespeichert.');
    }

    public function generateAi(Request $request, Expert $expert, ExpertCv $cv): RedirectResponse
    {
        abort_if((int) $cv->expert_id !== (int) $expert->id, 404);

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
        if (in_array($validated['field'], ['experience', 'education', 'skills'], true)) {
            $payload[$validated['field']] = TextLines::fromInput($text);
        } else {
            $payload[$validated['field']] = $text;
        }
        $this->cvs->updateDraft($cv, $payload);

        return back()->with('success', 'KI-Inhalt eingefügt.');
    }

    public function preview(Expert $expert, ExpertCv $cv): Response
    {
        abort_if((int) $cv->expert_id !== (int) $expert->id, 404);

        return response($this->cvs->renderHtml($cv))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function finalize(Expert $expert, ExpertCv $cv): RedirectResponse
    {
        abort_if((int) $cv->expert_id !== (int) $expert->id, 404);
        $this->cvs->finalize($cv);

        return back()->with('success', 'CV finalisiert (HTML — Drucken als PDF im Browser).');
    }

    public function destroy(Expert $expert, ExpertCv $cv): RedirectResponse
    {
        abort_if((int) $cv->expert_id !== (int) $expert->id, 404);
        $cv->delete();

        return redirect()->route('admin.experts.cv.index', $expert)->with('success', 'CV gelöscht.');
    }

    /** @return array<string, mixed> */
    private function validatedPayload(Request $request, Expert $expert): array
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
            'photo_path' => $expert->contact_photo_path,
        ];
    }
}
