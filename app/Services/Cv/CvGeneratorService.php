<?php

namespace App\Services\Cv;

use App\Models\Expert;
use App\Models\ExpertCv;
use App\Services\AI\AIManager;
use App\Support\ProfessionalGroup;
use App\Support\TextLines;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CvGeneratorService
{
    public const TEMPLATES = [
        'classic' => 'Klassisch',
        'modern' => 'Modern Sidebar',
        'executive' => 'Executive',
        'swiss' => 'Swiss Clean',
        'compact' => 'Kompakt',
        'timeline' => 'Timeline',
        'bold' => 'Bold Header',
        'elegant' => 'Elegant',
        'ai_design' => 'KI-Design',
    ];

    public const TEMPLATE_HINTS = [
        'classic' => 'Einspaltig, printfreundlich',
        'modern' => 'Farbige Sidebar links',
        'executive' => 'Editorial, seriös',
        'swiss' => 'Klar, helle Typografie',
        'compact' => 'Dicht, eine Seite',
        'timeline' => 'Erfahrung als Zeitstrahl',
        'bold' => 'Starker Farbkopf',
        'elegant' => 'Fein, luftig',
        'ai_design' => 'Layout von der KI gewählt',
    ];

    /** Accent colors for live design. */
    public const COLORS = [
        '#0E4971' => 'Nachtblau',
        '#0B6E4F' => 'Smaragd',
        '#B45309' => 'Bernstein',
        '#7C2D12' => 'Terrakotta',
        '#1E3A5F' => 'Navy',
        '#334155' => 'Schiefer',
        '#0F766E' => 'Petrol',
        '#9F1239' => 'Weinrot',
    ];

    public const LAYOUT_STYLES = ['sidebar_left', 'sidebar_right', 'banner', 'centered', 'split'];

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
            'layout' => $this->defaultLayout(),
        ];
    }

    /** @return array<string, string> */
    public function defaultLayout(): array
    {
        return [
            'style' => 'centered',
            'density' => 'normal',
            'type_mood' => 'sans',
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
        // Keep finalized status when editing design/content so library preview stays current.
        $data = [
            'payload' => $payload,
            'status' => $cv->status === 'ready' ? 'ready' : 'draft',
        ];
        if ($templateKey !== null && array_key_exists($templateKey, self::TEMPLATES)) {
            $data['template_key'] = $templateKey;
        }
        if ($title !== null && trim($title) !== '') {
            $data['title'] = trim($title);
        }
        $cv->update($data);
        $cv = $cv->fresh();
        $this->syncRenderedHtml($cv);

        return $cv->fresh();
    }

    /** Rewrite stored HTML so library / Ansehen always matches the latest design. */
    public function syncRenderedHtml(ExpertCv $cv): ExpertCv
    {
        $html = $this->renderHtml($cv);
        Storage::disk('public')->makeDirectory('experts/cvs');
        $path = $cv->pdf_path ?: ('experts/cvs/cv-'.$cv->expert_id.'-'.$cv->id.'.html');
        Storage::disk('public')->put($path, $html);

        $updates = [];
        if ($cv->pdf_path !== $path) {
            $updates['pdf_path'] = $path;
        }
        if ($updates !== []) {
            $cv->update($updates);
            $cv = $cv->fresh();
        }
        if ($cv->status === 'ready') {
            $cv->expert?->update(['cv_path' => $path]);
        }

        return $cv;
    }

    /** Kept for UI labels / optional single-field regen. */
    public static function generationSteps(): array
    {
        return [
            ['id' => 'full', 'label' => 'Gesamter Lebenslauf', 'kind' => 'full'],
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
     * One AI call for the whole CV (like ChatGPT) — typically under a minute.
     *
     * @param  array<string, mixed>  $context
     * @return array{payload: array<string, mixed>, template_key: string, title: ?string}
     */
    public function generateFullCv(Expert $expert, array $context = []): array
    {
        $seed = array_merge($this->seedFromExpert($expert), $context['payload'] ?? []);
        $preferred = (string) ($context['template_key'] ?? $seed['template_key'] ?? 'classic');
        $inventDesign = (bool) ($context['invent_design'] ?? ($preferred === 'ai_design'));
        $templateKeys = implode(', ', array_keys(self::TEMPLATES));

        $areas = $seed['experience_areas'] ?? [];
        if (is_array($areas)) {
            $areas = implode(', ', $areas);
        }
        $diplomas = $seed['diplomas'] ?? [];
        if (is_array($diplomas)) {
            $diplomas = implode(', ', $diplomas);
        }

        $prompt = "Du bist ein Schweizer Karriere-Texter für Nachfolge, M&A, Treuhand und Ad Interim.\n"
            ."Erzeuge in EINEM Durchgang einen vollständigen professionellen Lebenslauf.\n"
            ."Antworte NUR mit gültigem JSON (kein Markdown, keine Erklärung) in genau diesem Schema:\n"
            ."{\n"
            ."  \"headline\": \"string\",\n"
            ."  \"summary\": \"3-5 Sätze Deutsch CH\",\n"
            ."  \"services\": \"Leistungsprofil als Text\",\n"
            ."  \"experience\": [\"Rolle · Firma · Zeitraum — Kurztext\", \"...\"],\n"
            ."  \"education\": [\"Abschluss · Institution · Jahr\", \"...\"],\n"
            ."  \"skills\": [\"Kompetenz\", \"...\"],\n"
            ."  \"languages\": [\"Deutsch\", \"Englisch\"],\n"
            ."  \"template_key\": \"einer von: {$templateKeys}\",\n"
            ."  \"accent_color\": \"#RRGGBB\",\n"
            ."  \"title\": \"Kurztitel für den CV\",\n"
            ."  \"layout\": {\"style\":\"sidebar_left|sidebar_right|banner|centered|split\",\"density\":\"airy|normal|compact\",\"type_mood\":\"sans|serif|mixed\"}\n"
            ."}\n\n"
            .'Name: '.($seed['full_name'] ?? '')."\n"
            .'Unternehmen: '.($seed['company'] ?? '')."\n"
            .'Aktuelle Headline: '.($seed['headline'] ?? '')."\n"
            .'Standort: '.($seed['location'] ?? '')."\n"
            .'E-Mail: '.($seed['email'] ?? '')."\n"
            .'Telefon: '.($seed['phone'] ?? '')."\n"
            .'Website: '.($seed['website'] ?? '')."\n"
            .'Eigenkapital: '.($seed['equity_capital'] ?? '')."\n"
            .'Bestehende Beschreibung: '.mb_substr((string) ($seed['summary'] ?? ''), 0, 900)."\n"
            .'Leistungen: '.mb_substr((string) ($seed['services'] ?? ''), 0, 600)."\n"
            .'Erfahrungsbereiche: '.$areas."\n"
            .'Diplome: '.$diplomas."\n"
            .'Zusatzhinweise: '.mb_substr((string) ($context['notes'] ?? ''), 0, 600)."\n"
            .'Bevorzugte Vorlage: '.$preferred."\n"
            .($inventDesign
                ? "Wähle template_key = \"ai_design\" und setze layout sinnvoll (professionell, schweizerisch, nicht verspielt).\n"
                : "Behalte template_key möglichst bei «{$preferred}», es sei denn eine andere Vorlage passt klar besser.\n")
            ."Nutze realistische, glaubwürdige Formulierungen auf Basis der Angaben. Erfinde keine falschen Arbeitgeber-Namen, wenn keine Erfahrung vorliegt — formuliere dann allgemeine Stationen/Schwerpunkte aus den Erfahrungsbereichen.\n"
            .'Arrays: 3–8 Einträge wo sinnvoll. Keine leeren Strings.';

        $raw = trim($this->ai->complete($prompt, 'cv_full'));
        $parsed = $this->parseJsonObject($raw);
        if ($parsed === null) {
            throw new \RuntimeException('KI-Antwort war kein gültiges JSON. Bitte erneut versuchen.');
        }

        $payload = $seed;
        foreach (['headline', 'summary', 'services', 'title'] as $key) {
            if (! empty($parsed[$key]) && is_string($parsed[$key])) {
                if ($key === 'title') {
                    continue;
                }
                $payload[$key] = trim($parsed[$key]);
            }
        }
        foreach (['experience', 'education', 'skills', 'languages'] as $key) {
            if (isset($parsed[$key])) {
                $payload[$key] = $this->normalizeList($parsed[$key]);
            }
        }

        $accent = (string) ($parsed['accent_color'] ?? $seed['accent_color'] ?? '#0E4971');
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $accent)) {
            $payload['accent_color'] = strtoupper($accent);
        }

        // Always keep the user's chosen template unless they asked for AI design.
        if ($inventDesign || $preferred === 'ai_design') {
            $templateKey = 'ai_design';
        } elseif (array_key_exists($preferred, self::TEMPLATES)) {
            $templateKey = $preferred;
        } else {
            $templateKey = 'classic';
        }

        if ($templateKey === 'ai_design') {
            $payload['layout'] = $this->normalizeLayout($parsed['layout'] ?? ($seed['layout'] ?? null));
            if (empty($parsed['accent_color']) && empty($seed['accent_color'])) {
                $payload['accent_color'] = $payload['accent_color'] ?? '#0E4971';
            }
        } else {
            $payload['layout'] = $this->normalizeLayout($seed['layout'] ?? null);
        }

        $title = null;
        if (! empty($parsed['title']) && is_string($parsed['title'])) {
            $title = trim($parsed['title']);
        }

        return [
            'payload' => $payload,
            'template_key' => $templateKey,
            'title' => $title,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function mergeGeneratedField(array $payload, string $field, string $text): array
    {
        $listFields = ['experience', 'education', 'skills', 'experience_areas', 'diplomas', 'criteria', 'languages'];
        if (in_array($field, $listFields, true)) {
            $normalized = str_replace([', ', ' • ', ' · '], "\n", $text);
            $payload[$field] = TextLines::fromInput($normalized);
        } else {
            $payload[$field] = $text;
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    public function toStudioArray(ExpertCv $cv): array
    {
        $updatedTs = optional($cv->updated_at)->timestamp ?: time();

        return [
            'id' => $cv->id,
            'title' => $cv->title,
            'template_key' => $cv->template_key,
            'status' => $cv->status,
            'payload' => $cv->payload ?? [],
            'pdf_url' => $cv->pdf_path ? asset('storage/'.$cv->pdf_path).'?v='.$updatedTs : null,
            'has_file' => (bool) $cv->pdf_path,
            'updated_at' => optional($cv->updated_at)->toIso8601String(),
            'updated_label' => optional($cv->updated_at)->format('d.m.Y H:i'),
        ];
    }

    public function suggestTemplate(Expert $expert, array $payload = []): string
    {
        $group = $expert->professional_group ?? ProfessionalGroup::MA_SPECIALIST;
        $fallback = match ($group) {
            ProfessionalGroup::AD_INTERIM => 'modern',
            ProfessionalGroup::JURIST => 'classic',
            ProfessionalGroup::TREUHAND => 'swiss',
            default => 'executive',
        };

        try {
            $keys = implode(', ', array_keys(self::TEMPLATES));
            $prompt = "Wähle genau eines dieser CV-Templates: {$keys}. "
                .'Antworte nur mit dem Schlüssel. Fachgruppe: '.ProfessionalGroup::label($group)
                .'. Name: '.($payload['full_name'] ?? $expert->publicDisplayName())
                .'. Kurz: '.mb_substr((string) ($payload['summary'] ?? $expert->description ?? ''), 0, 280);
            $raw = strtolower(trim($this->ai->complete($prompt, 'cv_template')));
            foreach (array_keys(self::TEMPLATES) as $key) {
                if (str_contains($raw, $key)) {
                    return $key;
                }
            }
        } catch (\Throwable) {
            // fallback below
        }

        return $fallback;
    }

    /**
     * AI invents layout primitives for the flexible ai_design template (fast, no content rewrite).
     *
     * @param  array<string, mixed>  $payload
     * @return array{template_key: string, accent_color: string, layout: array<string, string>}
     */
    public function inventDesign(Expert $expert, array $payload = []): array
    {
        $presets = [
            ['accent_color' => '#0E4971', 'layout' => ['style' => 'sidebar_left', 'density' => 'normal', 'type_mood' => 'sans']],
            ['accent_color' => '#0B6E4F', 'layout' => ['style' => 'banner', 'density' => 'airy', 'type_mood' => 'mixed']],
            ['accent_color' => '#1E3A5F', 'layout' => ['style' => 'split', 'density' => 'normal', 'type_mood' => 'serif']],
            ['accent_color' => '#7C2D12', 'layout' => ['style' => 'sidebar_right', 'density' => 'compact', 'type_mood' => 'sans']],
            ['accent_color' => '#0F766E', 'layout' => ['style' => 'centered', 'density' => 'airy', 'type_mood' => 'serif']],
        ];
        $fallback = $presets[array_rand($presets)];

        $prompt = "Entwirf ein CV-Layout für einen Schweizer Experten. Antworte NUR mit JSON:\n"
            ."{\"accent_color\":\"#RRGGBB\",\"layout\":{\"style\":\"sidebar_left|sidebar_right|banner|centered|split\",\"density\":\"airy|normal|compact\",\"type_mood\":\"sans|serif|mixed\"}}\n"
            .'Fachgruppe: '.ProfessionalGroup::label($expert->professional_group ?? ProfessionalGroup::MA_SPECIALIST)."\n"
            .'Name: '.($payload['full_name'] ?? $expert->publicDisplayName())."\n"
            .'Headline: '.($payload['headline'] ?? '');

        try {
            $parsed = $this->parseJsonObject(trim($this->ai->complete($prompt, 'cv_invent_design'))) ?? [];
        } catch (\Throwable) {
            $parsed = [];
        }

        if ($parsed === []) {
            return [
                'template_key' => 'ai_design',
                'accent_color' => $fallback['accent_color'],
                'layout' => $this->normalizeLayout($fallback['layout']),
            ];
        }

        $accent = (string) ($parsed['accent_color'] ?? $fallback['accent_color']);
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $accent)) {
            $accent = $fallback['accent_color'];
        }

        return [
            'template_key' => 'ai_design',
            'accent_color' => strtoupper($accent),
            'layout' => $this->normalizeLayout($parsed['layout'] ?? $fallback['layout']),
        ];
    }

    public function renderHtml(ExpertCv $cv): string
    {
        $cv->loadMissing('expert');
        $template = array_key_exists($cv->template_key, self::TEMPLATES) ? $cv->template_key : 'classic';
        $payload = $cv->payload ?? [];
        if (empty($payload['accent_color'])) {
            $payload['accent_color'] = '#0E4971';
        }
        if (empty($payload['layout']) || ! is_array($payload['layout'])) {
            $payload['layout'] = $this->defaultLayout();
        }

        return view('cv.templates.'.$template, [
            'cv' => $cv,
            'expert' => $cv->expert,
            'payload' => $payload,
            'accent' => $payload['accent_color'],
            'layout' => $payload['layout'],
        ])->render();
    }

    /**
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
        if (empty($payload['layout']) || ! is_array($payload['layout'])) {
            $payload['layout'] = $this->defaultLayout();
        }

        return view('cv.templates.'.$template, [
            'cv' => null,
            'expert' => $expert,
            'payload' => $payload,
            'accent' => $payload['accent_color'],
            'layout' => $payload['layout'],
        ])->render();
    }

    public function finalize(ExpertCv $cv): ExpertCv
    {
        $cv->update(['status' => 'ready']);
        $cv = $this->syncRenderedHtml($cv->fresh());
        $cv->expert?->update(['cv_path' => $cv->pdf_path]);

        return $cv->fresh();
    }

    /** @return array<string, mixed>|null */
    private function parseJsonObject(string $raw): ?array
    {
        $raw = trim($raw);
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/is', $raw, $m)) {
            $raw = $m[1];
        } elseif (preg_match('/\{.*\}/s', $raw, $m)) {
            $raw = $m[0];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @return list<string> */
    private function normalizeList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $value)));
        }
        if (is_string($value)) {
            return TextLines::fromInput(str_replace([', ', ' • ', ' · '], "\n", $value));
        }

        return [];
    }

    /** @return array<string, string> */
    private function normalizeLayout(mixed $layout): array
    {
        $base = $this->defaultLayout();
        if (! is_array($layout)) {
            return $base;
        }
        $style = (string) ($layout['style'] ?? $base['style']);
        if (! in_array($style, self::LAYOUT_STYLES, true)) {
            $style = $base['style'];
        }
        $density = (string) ($layout['density'] ?? $base['density']);
        if (! in_array($density, ['airy', 'normal', 'compact'], true)) {
            $density = $base['density'];
        }
        $mood = (string) ($layout['type_mood'] ?? $base['type_mood']);
        if (! in_array($mood, ['sans', 'serif', 'mixed'], true)) {
            $mood = $base['type_mood'];
        }

        return [
            'style' => $style,
            'density' => $density,
            'type_mood' => $mood,
        ];
    }
}
