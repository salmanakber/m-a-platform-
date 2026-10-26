<?php

namespace App\Services\Article;

use App\Models\ArticleCategory;
use App\Models\Expert;
use App\Services\AI\AIManager;
use App\Support\ContentHtml;
use Illuminate\Support\Str;

class ArticleAiService
{
    public function __construct(private AIManager $ai)
    {
    }

    /**
     * Fast first pass — real title, excerpt, outline for live progress.
     *
     * @return array{title: string, excerpt: string, headings: list<string>, category_id: int|null, category: ?string}
     */
    public function generateOutline(Expert $expert, string $topic, ?string $tone = null, ?int $categoryId = null): array
    {
        $categories = ArticleCategory::query()->orderBy('sort_order')->get(['id', 'name_de']);
        $categoryList = $categories->pluck('name_de')->implode(', ');
        $preferredCategory = $categories->firstWhere('id', $categoryId)?->name_de;
        $toneText = $tone ?: 'fachlich, klar, vertrauenswürdig';

        $prompt = <<<PROMPT
Du bist Autor für nachfolge-experten.ch (Schweiz, M&A / Nachfolge).
Erstelle NUR die Struktur eines Fachartikels — noch keinen Fliesstext.

Firma: {$expert->company_name}
Thema: {$topic}
Tonalität: {$toneText}
Bevorzugte Kategorie: {$preferredCategory}
Erlaubte Kategorien: {$categoryList}

Antworte NUR mit JSON:
{
  "title": "Klarer Fach-Titel",
  "excerpt": "1–2 Sätze, max 220 Zeichen",
  "headings": ["H2-Überschrift 1", "H2-Überschrift 2", "H2-Überschrift 3"],
  "category": "eine der erlaubten Kategorien"
}

Regeln:
- 3 bis 5 headings, praxisnah für KMU
- Keine erfundenen Gesetze oder Kontaktdaten
PROMPT;

        $raw = $this->ai->complete($prompt, 'expert_article_outline');
        $json = $this->extractJsonObject($raw);
        if ($json === null) {
            throw new \RuntimeException('Die KI-Struktur konnte nicht gelesen werden.');
        }

        $title = trim((string) ($json['title'] ?? ''));
        $excerpt = trim((string) ($json['excerpt'] ?? ''));
        $headings = array_values(array_filter(array_map(
            static fn ($h) => trim((string) $h),
            is_array($json['headings'] ?? null) ? $json['headings'] : []
        )));
        $categoryName = trim((string) ($json['category'] ?? ''));

        if ($title === '') {
            throw new \RuntimeException('Die KI hat keinen Titel geliefert.');
        }
        if ($headings === []) {
            $headings = ['Ausgangslage', 'Vorgehen in der Praxis', 'Nächste Schritte'];
        }

        return [
            'title' => Str::limit($title, 240),
            'excerpt' => Str::limit($excerpt !== '' ? $excerpt : Str::limit($topic, 180), 500),
            'headings' => array_slice($headings, 0, 6),
            'category_id' => $this->resolveCategoryId($categories, $categoryId, $categoryName),
            'category' => $categoryName !== '' ? $categoryName : $preferredCategory,
        ];
    }

    /**
     * Second pass — full formatted HTML body from outline.
     *
     * @param  list<string>  $headings
     * @return array{body: string}
     */
    public function generateBody(
        Expert $expert,
        string $topic,
        string $title,
        array $headings,
        ?string $tone = null,
        ?string $excerpt = null
    ): array {
        $toneText = $tone ?: 'fachlich, klar, vertrauenswürdig';
        $headingList = implode("\n", array_map(static fn ($h) => '- '.$h, $headings));

        $prompt = <<<PROMPT
Du bist Autor für nachfolge-experten.ch.
Schreibe den vollständigen Fachartikel auf Deutsch (Schweiz) als HTML.

Firma: {$expert->company_name}
Thema: {$topic}
Titel: {$title}
Auszug: {$excerpt}
Tonalität: {$toneText}
Gliederung (als <h2> verwenden):
{$headingList}

Antworte NUR mit JSON:
{
  "body": "HTML"
}

HTML-Anforderungen:
- Einleitung als <p>, dann für jede Gliederungsüberschrift ein <h2> plus 1–2 <p>
- Mindestens eine <ul> mit 3–5 <li> an sinnvoller Stelle
- Optional <strong> für Schlüsselbegriffe
- Kein Markdown, keine Code-Fences, nur HTML-Tags: p, h2, h3, ul, ol, li, strong, em, a
- Keine erfundenen Gesetzesnummern oder Telefonnummern
- Praxisnah und konkret für KMU / Nachfolge
PROMPT;

        $raw = $this->ai->complete($prompt, 'expert_article_body');
        $json = $this->extractJsonObject($raw);
        if ($json === null) {
            throw new \RuntimeException('Der Artikeltext konnte nicht gelesen werden.');
        }

        $body = ContentHtml::normalize(trim((string) ($json['body'] ?? '')));
        if ($body === '') {
            throw new \RuntimeException('Die KI hat keinen Artikeltext geliefert.');
        }

        // Ensure outline headings appear if the model skipped them.
        foreach ($headings as $heading) {
            if ($heading !== '' && ! str_contains($body, $heading)) {
                $body .= "\n<h2>".e($heading)."</h2>\n<p>…</p>";
            }
        }

        return ['body' => $body];
    }

    /**
     * @return array{title: string, excerpt: string, body: string, category_id: int|null, headings: list<string>}
     */
    public function generate(Expert $expert, string $topic, ?string $tone = null, ?int $categoryId = null): array
    {
        $outline = $this->generateOutline($expert, $topic, $tone, $categoryId);
        $body = $this->generateBody(
            $expert,
            $topic,
            $outline['title'],
            $outline['headings'],
            $tone,
            $outline['excerpt']
        );

        return [
            'title' => $outline['title'],
            'excerpt' => $outline['excerpt'],
            'body' => $body['body'],
            'category_id' => $outline['category_id'],
            'headings' => $outline['headings'],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ArticleCategory>  $categories
     */
    private function resolveCategoryId($categories, ?int $categoryId, string $categoryName): ?int
    {
        if ($categoryId !== null) {
            return $categoryId;
        }
        if ($categoryName !== '') {
            $found = $categories->first(function ($cat) use ($categoryName) {
                return strcasecmp($cat->name_de, $categoryName) === 0;
            });
            if ($found) {
                return (int) $found->id;
            }
        }

        return $categories->first()?->id ? (int) $categories->first()->id : null;
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
