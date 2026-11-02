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

    /** Accent colors for live design control room. */
    public const COLORS = [
        '#0E4971' => 'Nachtblau',
        '#0B6E4F' => 'Smaragd',
        '#B45309' => 'Bernstein',
        '#7C2D12' => 'Terrakotta',
        '#1E3A5F' => 'Navy',
        '#334155' => 'Schiefer',
        '#6D28D9' => 'Violett',
        '#0F766E' => 'Petrol',
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
            'accent_color' => '#0E4971',
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

    /** Ordered AI steps for the studio progress UI. */
    public static function generationSteps(): array
    {
        return [
            ['id' => 'summary', 'label' => 'Professionelle Zusammenfassung', 'kind' => 'text'],
            ['id' => 'services', 'label' => 'Leistungsprofil', 'kind' => 'text'],
            ['id' => 'experience', 'label' => 'Berufserfahrung', 'kind' => 'list'],
            ['id' => 'education', 'label' => 'Ausbildung', 'kind' => 'list'],
            ['id' => 'skills', 'label' => 'Kompetenzen', 'kind' => 'list'],
        ];
    }

    public function generateField(Expert $expert, string $field, array $context = []): string
    {
        $seed = array_merge($this->seedFromExpert($expert), $context['payload'] ?? []);
        $fieldLabels = [
            'summary' => 'Professionelle Zusammenfassung (3–5 Sätze, Deutsch CH)',
            'services' => 'Leistungsbeschreibung (Stichpunkte oder kurze Absätze)',
            'experience' => 'Berufserfahrung als strukturierte Liste — eine Zeile pro Station: Rolle · Firma · Zeitraum — Kurzbeschreibung',
            'education' => 'Ausbildung / Diplome — eine Zeile pro Eintrag',
            'skills' => 'Kompetenzen — eine Zeile oder Komma-getrennt',
        ];
        $label = $fieldLabels[$field] ?? 'Professioneller CV-Abschnitt «'.$field.'»';

        $areas = $seed['experience_areas'] ?? [];
        if (is_array($areas)) {
            $areas = implode(', ', $areas);
        }

        $prompt = "Du schreibst einen professionellen Schweizer Lebenslauf-Abschnitt für einen Nachfolge-/M&A-Experten.\n"
            ."Feld: {$label}\n"
            .'Name: '.($seed['full_name'] ?? '')."\n"
            .'Unternehmen: '.($seed['company'] ?? '')."\n"
            .'Fachgruppe: '.($seed['headline'] ?? '')."\n"
            .'Standort: '.($seed['location'] ?? '')."\n"
            .'Eigenkapital: '.($seed['equity_capital'] ?? '')."\n"
            .'Bestehende Beschreibung: '.mb_substr((string) ($seed['summary'] ?? ''), 0, 800)."\n"
            .'Leistungen: '.mb_substr((string) ($seed['services'] ?? ''), 0, 500)."\n"
            .'Erfahrungsbereiche: '.$areas."\n"
            .'Zusatzkontext: '.mb_substr((string) ($context['notes'] ?? ''), 0, 500)."\n"
            ."Schreibe auf Deutsch (Schweiz). Keine Anrede, kein Markdown-Codeblock, keine Überschrift. Nur den Inhalt.";

        return trim($this->ai->complete($prompt, 'cv_'.$field));
    }

    /**
     * Merge AI output into payload for a given field.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function mergeGeneratedField(array $payload, string $field, string $text): array
    {
        $listFields = ['experience', 'education', 'skills', 'experience_areas', 'diplomas', 'criteria', 'languages'];
        if (in_array($field, $listFields, true)) {
            $normalized = str_replace([', ', ' • ', ' · '], "\n", $text);
            $payload[$field] = \App\Support\TextLines::fromInput($normalized);
        } else {
            $payload[$field] = $text;
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    public function toStudioArray(ExpertCv $cv): array
    {
        return [
            'id' => $cv->id,
            'title' => $cv->title,
            'template_key' => $cv->template_key,
            'status' => $cv->status,
            'payload' => $cv->payload ?? [],
            'pdf_url' => $cv->pdf_path ? asset('storage/'.$cv->pdf_path) : null,
            'updated_at' => optional($cv->updated_at)->toIso8601String(),
            'updated_label' => optional($cv->updated_at)->format('d.m.Y H:i'),
        ];
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
        $payload = $cv->payload ?? [];
        if (empty($payload['accent_color'])) {
            $payload['accent_color'] = '#0E4971';
        }

        return view('cv.templates.'.$template, [
            'cv' => $cv,
            'expert' => $cv->expert,
            'payload' => $payload,
            'accent' => $payload['accent_color'],
        ])->render();
    }

    /**
     * Live preview without persisting (control room).
     *
     * @param  array<string, mixed>  $payload
     */
    public function renderPreviewHtml(Expert $expert, string $templateKey, array $payload): string
    {
        $template = array_key_exists($templateKey, self::TEMPLATES) ? $templateKey : 'classic';
        if (empty($payload['accent_color'])) {
            $payload['accent_color'] = '#0E4971';
        }
        if (empty($payload['photo_path']) && $expert->contact_photo_path) {
            $payload['photo_path'] = $expert->contact_photo_path;
        }

        return view('cv.templates.'.$template, [
            'cv' => null,
            'expert' => $expert,
            'payload' => $payload,
            'accent' => $payload['accent_color'],
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
