<?php

namespace App\Support;

class ContentHtml
{
    /**
     * Normalize AI / crawled HTML into safe, editor-friendly markup.
     */
    public static function normalize(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        // Strip accidental markdown fences.
        $html = preg_replace('/^```(?:html|markdown)?\s*/i', '', $html) ?? $html;
        $html = preg_replace('/\s*```$/', '', $html) ?? $html;
        $html = trim($html);

        // Plain text → paragraphs.
        if (! preg_match('/<[a-z][\s\S]*>/i', $html)) {
            $chunks = preg_split("/\n{2,}/", $html) ?: [$html];
            $parts = [];
            foreach ($chunks as $chunk) {
                $chunk = trim($chunk);
                if ($chunk === '') {
                    continue;
                }
                // Markdown-ish headings
                if (preg_match('/^##\s+(.+)$/u', $chunk, $m)) {
                    $parts[] = '<h2>'.e(trim($m[1])).'</h2>';
                    continue;
                }
                if (preg_match('/^###\s+(.+)$/u', $chunk, $m)) {
                    $parts[] = '<h3>'.e(trim($m[1])).'</h3>';
                    continue;
                }
                $lines = preg_split("/\n/", $chunk) ?: [$chunk];
                $listItems = [];
                $isList = true;
                foreach ($lines as $line) {
                    if (preg_match('/^\s*[-*•]\s+(.+)$/u', $line, $m)) {
                        $listItems[] = '<li>'.e(trim($m[1])).'</li>';
                    } else {
                        $isList = false;
                        break;
                    }
                }
                if ($isList && $listItems !== []) {
                    $parts[] = '<ul>'.implode('', $listItems).'</ul>';
                    continue;
                }
                $parts[] = '<p>'.nl2br(e($chunk), false).'</p>';
            }
            $html = implode("\n", $parts);
        }

        $html = strip_tags(
            $html,
            '<p><br><br/><h2><h3><ul><ol><li><strong><b><em><i><a><blockquote>'
        );

        // Drop empty paragraphs.
        $html = preg_replace('/<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>/i', '', $html) ?? $html;

        return trim($html);
    }
}
