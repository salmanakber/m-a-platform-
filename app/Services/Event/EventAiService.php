<?php

namespace App\Services\Event;

use App\Models\Expert;
use App\Services\AI\AIManager;
use App\Support\ContentHtml;

class EventAiService
{
    public function __construct(private AIManager $ai)
    {
    }

    /**
     * Fast meta draft for live progress.
     *
     * @return array{
     *   title: string,
     *   event_type: string,
     *   location_name: ?string,
     *   city: ?string,
     *   starts_on: ?string,
     *   starts_at_time: ?string,
     *   source_url: ?string,
     *   needs_review: bool,
     *   notes: string,
     *   outline: list<string>
     * }
     */
    public function generateOutline(Expert $expert, string $briefing, ?string $eventType = null): array
    {
        $type = $eventType ?: 'other';

        $prompt = <<<PROMPT
Du bist Redakteur für nachfolge-experten.ch.
Erstelle NUR die Meta-Daten eines Event-Entwurfs (noch keine lange Beschreibung).

Firma: {$expert->company_name}
Website: {$expert->website}
Briefing: {$briefing}
Vorgeschlagener Typ: {$type}

Antworte NUR mit JSON:
{
  "title": "...",
  "event_type": "workshop|presentation|hearing|other",
  "location_name": null,
  "city": null,
  "starts_on": null,
  "starts_at_time": null,
  "source_url": null,
  "needs_review": true,
  "notes": "kurze Begründung",
  "outline": ["Punkt 1", "Punkt 2", "Punkt 3"]
}

Regeln:
- Erfinde KEINE konkreten Daten, Uhrzeiten oder Adressen.
- outline: 3 kurze inhaltliche Schwerpunkte des Events
PROMPT;

        $raw = $this->ai->complete($prompt, 'expert_event_outline');
        $json = $this->extractJsonObject($raw);
        if ($json === null) {
            throw new \RuntimeException('Die KI-Antwort konnte nicht gelesen werden.');
        }

        $title = trim((string) ($json['title'] ?? ''));
        if ($title === '') {
            throw new \RuntimeException('Unvollständiger KI-Entwurf.');
        }

        $startsOn = $this->nullableDate($json['starts_on'] ?? null);
        $needsReview = (bool) ($json['needs_review'] ?? true) || $startsOn === null;
        $outline = array_values(array_filter(array_map(
            static fn ($h) => trim((string) $h),
            is_array($json['outline'] ?? null) ? $json['outline'] : []
        )));
        if ($outline === []) {
            $outline = ['Zielgruppe & Nutzen', 'Ablauf', 'Nächste Schritte'];
        }

        return [
            'title' => mb_substr($title, 0, 255),
            'event_type' => in_array(($json['event_type'] ?? ''), ['workshop', 'presentation', 'hearing', 'other'], true)
                ? $json['event_type']
                : $type,
            'location_name' => $this->nullableString($json['location_name'] ?? null),
            'city' => $this->nullableString($json['city'] ?? null),
            'starts_on' => $startsOn,
            'starts_at_time' => $this->nullableTime($json['starts_at_time'] ?? null),
            'source_url' => filter_var((string) ($json['source_url'] ?? ''), FILTER_VALIDATE_URL) ?: null,
            'needs_review' => $needsReview,
            'notes' => trim((string) ($json['notes'] ?? 'Bitte vor Veröffentlichung prüfen.')),
            'outline' => array_slice($outline, 0, 6),
        ];
    }

    /**
     * @param  list<string>  $outline
     * @return array{description: string}
     */
    public function generateDescription(Expert $expert, string $briefing, string $title, array $outline, string $eventType): array
    {
        $points = implode("\n", array_map(static fn ($p) => '- '.$p, $outline));

        $prompt = <<<PROMPT
Schreibe die Event-Beschreibung auf Deutsch (Schweiz) als HTML für nachfolge-experten.ch.

Firma: {$expert->company_name}
Titel: {$title}
Typ: {$eventType}
Briefing: {$briefing}
Schwerpunkte:
{$points}

Antworte NUR mit JSON: { "description": "HTML" }

HTML-Anforderungen:
- 2–4 <p>-Absätze
- Eine <ul> mit den Schwerpunkten als <li>
- Optional ein kurzes <h3> «Ablauf» oder «Zielgruppe»
- Keine erfundenen Daten/Orte/Links
- Nur Tags: p, h3, ul, ol, li, strong, em
PROMPT;

        $raw = $this->ai->complete($prompt, 'expert_event_description');
        $json = $this->extractJsonObject($raw);
        if ($json === null) {
            throw new \RuntimeException('Beschreibung konnte nicht gelesen werden.');
        }

        $description = ContentHtml::normalize(trim((string) ($json['description'] ?? '')));
        if ($description === '') {
            throw new \RuntimeException('Leere Beschreibung.');
        }

        return ['description' => $description];
    }

    /**
     * @return array{
     *   title: string,
     *   description: string,
     *   event_type: string,
     *   location_name: ?string,
     *   city: ?string,
     *   starts_on: ?string,
     *   starts_at_time: ?string,
     *   source_url: ?string,
     *   needs_review: bool,
     *   notes: string,
     *   outline: list<string>
     * }
     */
    public function generateDraft(Expert $expert, string $briefing, ?string $eventType = null): array
    {
        $meta = $this->generateOutline($expert, $briefing, $eventType);
        $desc = $this->generateDescription(
            $expert,
            $briefing,
            $meta['title'],
            $meta['outline'],
            $meta['event_type']
        );

        return array_merge($meta, ['description' => $desc['description']]);
    }

    /** @return array<string, mixed>|null */
    private function extractJsonObject(string $raw): ?array
    {
        if (preg_match('/\{.*\}/s', trim($raw), $m)) {
            $decoded = json_decode($m[0], true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? mb_substr($value, 0, 255) : null;
    }

    private function nullableDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }

    private function nullableTime(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || ! preg_match('/^\d{2}:\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }
}
