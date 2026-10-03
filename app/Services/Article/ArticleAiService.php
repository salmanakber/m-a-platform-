<?php

namespace App\Services\Article;

use App\Models\ArticleCategory;
use App\Models\Expert;
use App\Services\AI\AIManager;
use Illuminate\Support\Str;

class ArticleAiService
{
    public function __construct(private AIManager $ai)
    {
    }

    /**
     * @return array{title: string, excerpt: string, body: string, category_id: int|null}
     */
    public function generate(Expert $expert, string $topic, ?string $tone = null, ?int $categoryId = null): array
    {
        $categories = ArticleCategory::query()->orderBy('sort_order')->get(['id', 'name_de']);
        $categoryList = $categories->pluck('name_de')->implode(', ');
        $preferredCategory = $categories->firstWhere('id', $categoryId)?->name_de;

        $toneText = $tone ?: 'fachlich, klar, vertrauenswürdig';

        $prompt = <<<PROMPT
Du bist Autor für nachfolge-experten.ch, dem Schweizer Verzeichnis für M&A- und Nachfolge-Experten.
Schreibe einen Fachartikel auf Deutsch (Schweiz).

Firma des Experten: {$expert->company_name}
Ansprechpartner: {$expert->contact_person_name} {$expert->contact_person_last_name}
Thema / Briefing: {$topic}
Tonalität: {$toneText}
Bevorzugte Kategorie: {$preferredCategory}
Erlaubte Kategorien: {$categoryList}

Antworte NUR mit einem gültigen JSON-Objekt (kein Markdown, keine Erklärung):
{
  "title": "Titel",
  "excerpt": "Kurzer Auszug max 220 Zeichen",
  "body": "HTML mit mehreren <p>-Absätzen, optional <h2>",
  "category": "eine der erlaubten Kategorien"
}

Anforderungen:
- Mindestens 4 Absätze, praxisnah für KMU / Nachfolge
- Keine erfundenen Gesetzesnummern
- Keine Telefonnummern oder privaten Kontaktdaten
- Kein Marketing-Blabla, sondern hilfreicher Fachinhalt
PROMPT;

        $raw = $this->ai->complete($prompt, 'expert_article_generate');
        $json = $this->extractJsonObject($raw);

        if ($json === null) {
            throw new \RuntimeException('Die KI-Antwort konnte nicht gelesen werden. Bitte erneut versuchen.');
        }

        $title = trim((string) ($json['title'] ?? ''));
        $excerpt = trim((string) ($json['excerpt'] ?? ''));
        $body = trim((string) ($json['body'] ?? ''));
        $categoryName = trim((string) ($json['category'] ?? ''));

        if ($title === '' || $body === '') {
            throw new \RuntimeException('Die KI hat keinen vollständigen Artikel geliefert.');
        }

        $resolvedCategoryId = $categoryId;
        if ($resolvedCategoryId === null && $categoryName !== '') {
            $resolvedCategoryId = $categories->first(function ($cat) use ($categoryName) {
                return strcasecmp($cat->name_de, $categoryName) === 0
                    || $cat->name_de === $categoryName;
            })?->id;
        }

        if ($resolvedCategoryId === null) {
            $resolvedCategoryId = $categories->first()?->id;
        }

        return [
            'title' => Str::limit($title, 240),
            'excerpt' => Str::limit($excerpt !== '' ? $excerpt : Str::limit(strip_tags($body), 220), 500),
            'body' => $body,
            'category_id' => $resolvedCategoryId ? (int) $resolvedCategoryId : null,
        ];
    }

    /** @return array<string, mixed>|null */
    private function extractJsonObject(string $raw): ?array
    {
        $raw = trim($raw);
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $decoded = json_decode($m[0], true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
