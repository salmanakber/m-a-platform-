<?php

namespace App\Services\Cv;

use App\Models\Expert;
use App\Models\ExpertCv;
use App\Services\AI\AIManager;
use App\Support\ProfessionalGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CvGeneratorService
{
    public const TEMPLATES = [
        'classic' => 'Klassisch',
        'modern' => 'Modern',
        'executive' => 'Executive',
    ];

    public function __construct(private AIManager $ai)
    {
    }

    /** @return array<string, mixed> */
    public function seedFromExpert(Expert $expert): array
    {
        $expert->loadMissing('offices.canton');
        $office = $expert->primaryOffice();
        $name = trim(($expert->contact_person_name ?? '').' '.($expert->contact_person_last_name ?? ''));
        if ($name === '') {
            $name = (string) $expert->company_name;
        }

        return [
            'full_name' => $name,
            'headline' => ProfessionalGroup::label($expert->professional_group ?? ProfessionalGroup::MA_SPECIALIST),
            'company' => $expert->company_name,
            'email' => $expert->email,
            'phone' => $expert->phone,
            'website' => $expert->website,
            'location' => $office
                ? trim(($office->city ?? '').($office->canton ? ', '.$office->canton->name_de : ''))
                : 'Schweiz',
            'summary' => (string) ($expert->description ?? ''),
            'services' => (string) ($expert->services_text ?? ''),
            'experience_areas' => array_values($expert->experience_areas ?? []),
            'diplomas' => array_values($expert->diplomas ?? []),
            'criteria' => array_values($expert->interim_criteria ?? []),
            'equity_capital' => (string) ($expert->equity_capital ?? ''),
            'experience' => [],
            'education' => [],
            'skills' => [],
            'languages' => ['Deutsch'],
            'photo_path' => $expert->contact_photo_path,
        ];
    }

    public function createDraft(Expert $expert, ?string $templateKey = 'classic', ?array $payload = null): ExpertCv
    {
        $templateKey = array_key_exists((string) $templateKey, self::TEMPLATES) ? (string) $templateKey : 'classic';

        return ExpertCv::query()->create([
            'expert_id' => $expert->id,
            'created_by' => Auth::id(),
            'title' => 'Lebenslauf — '.$expert->publicDisplayName(),
            'template_key' => $templateKey,
            'payload' => $payload ?? $this->seedFromExpert($expert),
            'status' => 'draft',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateDraft(ExpertCv $cv, array $payload, ?string $templateKey = null, ?string $title = null): ExpertCv
    {
        $data = [
            'payload' => $payload,
            'status' => 'draft',
        ];
        if ($templateKey !== null && array_key_exists($templateKey, self::TEMPLATES)) {
            $data['template_key'] = $templateKey;
        }
        if ($title !== null && trim($title) !== '') {
            $data['title'] = trim($title);
        }
        $cv->update($data);

        return $cv->fresh();
    }

    public function generateField(Expert $expert, string $field, array $context = []): string
    {
        $seed = $this->seedFromExpert($expert);
        $fieldLabels = [
            'summary' => 'Professionelle Zusammenfassung (3–5 Sätze, Deutsch CH)',
            'services' => 'Leistungsbeschreibung (Stichpunkte oder kurze Absätze)',
            'experience' => 'Berufserfahrung als strukturierte Liste (Rolle, Firma, Zeitraum, 2–3 Bulletpoints)',
            'education' => 'Ausbildung / Diplome als strukturierte Liste',
            'skills' => 'Kompetenzen als kommagetrennte Liste',
        ];
        $label = $fieldLabels[$field] ?? 'Professioneller CV-Abschnitt «'.$field.'»';

        $prompt = "Du schreibst einen professionellen Schweizer Lebenslauf-Abschnitt für einen Nachfolge-/M&A-Experten.\n"
            ."Feld: {$label}\n"
            .'Name: '.($seed['full_name'] ?? '')."\n"
            .'Unternehmen: '.($seed['company'] ?? '')."\n"
            .'Fachgruppe: '.($seed['headline'] ?? '')."\n"
            .'Standort: '.($seed['location'] ?? '')."\n"
            .'Bestehende Beschreibung: '.mb_substr((string) ($seed['summary'] ?? ''), 0, 800)."\n"
            .'Leistungen: '.mb_substr((string) ($seed['services'] ?? ''), 0, 500)."\n"
            .'Erfahrungsbereiche: '.implode(', ', $seed['experience_areas'] ?? [])."\n"
            .'Zusatzkontext: '.mb_substr((string) ($context['notes'] ?? ''), 0, 500)."\n"
            ."Schreibe auf Deutsch (Schweiz). Keine Anrede, kein Markdown-Codeblock. Nur den Inhalt.";

        return trim($this->ai->complete($prompt, 'cv_'.$field));
    }

    public function suggestTemplate(Expert $expert, array $payload = []): string
    {
        $group = $expert->professional_group ?? ProfessionalGroup::MA_SPECIALIST;
        if ($group === ProfessionalGroup::AD_INTERIM) {
            return 'modern';
        }
        if ($group === ProfessionalGroup::JURIST) {
            return 'classic';
        }

        try {
            $prompt = 'Wähle genau eines dieser CV-Templates für den Experten: classic, modern, executive. '
                .'Antworte nur mit dem Schlüssel. Fachgruppe: '.ProfessionalGroup::label($group)
                .'. Name: '.($payload['full_name'] ?? $expert->publicDisplayName());
            $raw = strtolower(trim($this->ai->complete($prompt, 'cv_template')));
            foreach (array_keys(self::TEMPLATES) as $key) {
                if (str_contains($raw, $key)) {
                    return $key;
                }
            }
        } catch (\Throwable) {
            // fallback below
        }

        return 'classic';
    }

    public function renderHtml(ExpertCv $cv): string
    {
        $cv->loadMissing('expert');
        $template = array_key_exists($cv->template_key, self::TEMPLATES) ? $cv->template_key : 'classic';

        return view('cv.templates.'.$template, [
            'cv' => $cv,
            'expert' => $cv->expert,
            'payload' => $cv->payload ?? [],
        ])->render();
    }

    public function finalize(ExpertCv $cv): ExpertCv
    {
        $html = $this->renderHtml($cv);
        Storage::disk('public')->makeDirectory('experts/cvs');
        $path = 'experts/cvs/cv-'.$cv->expert_id.'-'.$cv->id.'.html';
        Storage::disk('public')->put($path, $html);

        $cv->update([
            'pdf_path' => $path,
            'status' => 'ready',
        ]);

        $cv->expert?->update(['cv_path' => $path]);

        return $cv->fresh();
    }
}
