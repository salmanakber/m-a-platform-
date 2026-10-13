<?php

namespace App\Services\Event;

use App\Models\Expert;
use App\Services\AI\AIManager;

class EventAiService
{
    public function __construct(private AIManager $ai)
    {
    }

    /**
     * Returns a DRAFT only — never invents confirmed dates/locations.
     *
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
     *   notes: string
     * }
     */
    public function generateDraft(Expert $expert, string $briefing, ?string $eventType = null): array
    {
        $type = $eventType ?: 'other';

        $prompt = <<<PROMPT
Du bist Redakteur für nachfolge-experten.ch.
Erstelle einen ENTWURF für ein Fach-Event (Workshop, Vortrag, Hearing oder sonstig) auf Deutsch (Schweiz).

Firma: {$expert->company_name}
Website: {$expert->website}
Briefing: {$briefing}
Vorgeschlagener Typ: {$type}

Antworte NUR mit JSON:
{
  "title": "...",
  "description": "HTML mit <p>-Absätzen",
  "event_type": "workshop|presentation|hearing|other",
  "location_name": null,
  "city": null,
  "starts_on": null,
  "starts_at_time": null,
  "source_url": null,
  "needs_review": true,
  "notes": "kurze Begründung"
}

Regeln:
- Erfinde KEINE konkreten Datumswerte, Uhrzeiten oder Adressen.
- Wenn das Briefing kein Datum enthält, setze starts_on und starts_at_time auf null und needs_review=true.
- Wenn Ort unklar ist, location_name/city null lassen.
- Kennzeichne unsichere Angaben immer mit needs_review=true.
- Keine erfundenen Links.
PROMPT;

        $raw = $this->ai->complete($prompt, 'expert_event_generate');
        $json = $this->extractJsonObject($raw);

        if ($json === null) {
            throw new \RuntimeException('Die KI-Antwort konnte nicht gelesen werden.');
        }

        $title = trim((string) ($json['title'] ?? ''));
        $description = trim((string) ($json['description'] ?? ''));
        if ($title === '' || $description === '') {
            throw new \RuntimeException('Unvollständiger KI-Entwurf.');
        }

        $startsOn = $this->nullableDate($json['starts_on'] ?? null);
        $startsTime = $this->nullableTime($json['starts_at_time'] ?? null);

        // Force review if AI omitted critical facts.
        $needsReview = (bool) ($json['needs_review'] ?? true);
        if ($startsOn === null) {
            $needsReview = true;
        }

        return [
            'title' => mb_substr($title, 0, 255),
            'description' => $description,
            'event_type' => in_array(($json['event_type'] ?? ''), ['workshop', 'presentation', 'hearing', 'other'], true)
                ? $json['event_type']
                : $type,
            'location_name' => $this->nullableString($json['location_name'] ?? null),
            'city' => $this->nullableString($json['city'] ?? null),
            'starts_on' => $startsOn,
            'starts_at_time' => $startsTime,
            'source_url' => filter_var((string) ($json['source_url'] ?? ''), FILTER_VALIDATE_URL) ?: null,
            'needs_review' => $needsReview,
            'notes' => trim((string) ($json['notes'] ?? 'Bitte vor Veröffentlichung prüfen.')),
        ];
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
