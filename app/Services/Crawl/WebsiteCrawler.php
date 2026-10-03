<?php

namespace App\Services\Crawl;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebsiteCrawler
{
    private const MAX_DISCOVER = 50;

    private const MAX_PROCESS = 10;

    private const USER_AGENT = 'Mozilla/5.0 (compatible; NachfolgeExpertenBot/1.1; +https://nachfolge-experten.ch)';

    private const HUB_PATHS = [
        '/blog',
        '/blog/',
        '/news',
        '/news/',
        '/aktuelles',
        '/aktuell',
        '/wissen',
        '/insights',
        '/magazin',
        '/artikel',
        '/beitraege',
        '/beiträge',
        '/posts',
        '/de/blog',
        '/de/news',
        '/de/aktuelles',
        '/en/blog',
        '/en/news',
    ];

    /**
     * Discover candidate article URLs from a start URL, hubs, sitemap, and RSS.
     *
     * @return list<string>
     */
    public function discoverUrls(string $startUrl): array
    {
        $startUrl = $this->normalizeUrl($startUrl);

        if ($startUrl === null) {
            return [];
        }

        $baseHost = $this->hostKey((string) parse_url($startUrl, PHP_URL_HOST));
        $scheme = parse_url($startUrl, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($startUrl, PHP_URL_HOST);
        $origin = $scheme.'://'.$host;

        $candidates = [];
        $hubs = array_values(array_unique(array_filter([
            $startUrl,
            ...array_map(fn ($path) => $this->normalizeUrl($origin.$path), self::HUB_PATHS),
        ])));

        // Sitemap (incl. nested post sitemaps)
        foreach ($this->fetchSitemapUrls($origin.'/sitemap.xml', 0) as $url) {
            $candidates[] = $url;
        }
        foreach (['/sitemap_index.xml', '/wp-sitemap.xml', '/sitemap-index.xml', '/post-sitemap.xml', '/news-sitemap.xml'] as $extra) {
            foreach ($this->fetchSitemapUrls($origin.$extra, 0) as $url) {
                $candidates[] = $url;
            }
        }

        // RSS / Atom
        foreach (['/feed', '/rss', '/blog/feed', '/news/feed', '/atom.xml', '/index.xml', '/feed.xml'] as $feedPath) {
            foreach ($this->fetchRssUrls($origin.$feedPath) as $url) {
                $candidates[] = $url;
            }
        }

        // Crawl hubs for links
        foreach ($hubs as $hubUrl) {
            $html = $this->fetchHtml($hubUrl);
            if ($html === null) {
                continue;
            }
            foreach ($this->extractLinks($html, $hubUrl) as $url) {
                $candidates[] = $url;
            }
        }

        $scored = [];
        foreach ($candidates as $url) {
            $normalized = $this->normalizeUrl($url);
            if ($normalized === null) {
                continue;
            }
            if (! $this->sameSite($normalized, $baseHost)) {
                continue;
            }
            if ($this->isDeniedPath($normalized)) {
                continue;
            }

            $score = $this->scoreArticleUrl($normalized, $startUrl);
            if ($score <= 0) {
                continue;
            }
            $scored[$normalized] = max($score, $scored[$normalized] ?? 0);
        }

        // Always keep the start URL as a last-resort candidate (single-page import).
        if (! isset($scored[$startUrl])) {
            $scored[$startUrl] = 5;
        }

        arsort($scored);

        return array_slice(array_keys($scored), 0, self::MAX_DISCOVER);
    }

    /**
     * @return list<string>
     */
    public function urlsToProcess(array $discovered): array
    {
        return array_slice(array_values($discovered), 0, self::MAX_PROCESS);
    }

    /**
     * @return array{title: string, excerpt: string, body: string, image_url: ?string}|null
     */
    public function extractArticle(string $url): ?array
    {
        $html = $this->fetchHtml($url);

        if ($html === null || $html === '') {
            return null;
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        if (! $loaded) {
            return null;
        }

        $xpath = new DOMXPath($dom);

        $title = $this->firstText($xpath, [
            '//meta[@property="og:title"]/@content',
            '//h1',
            '//title',
        ]) ?? '';

        $title = trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($title === '') {
            return null;
        }

        $image = $this->firstText($xpath, [
            '//meta[@property="og:image"]/@content',
            '//meta[@name="twitter:image"]/@content',
        ]);

        if ($image) {
            $image = $this->absolutizeUrl($image, $url);
        }

        $bodyNode = $this->firstNode($xpath, [
            '//article',
            '//*[@itemprop="articleBody"]',
            '//*[contains(@class,"entry-content")]',
            '//*[contains(@class,"post-content")]',
            '//*[contains(@class,"article-content")]',
            '//*[contains(@class,"article-body")]',
            '//*[contains(@class,"content-body")]',
            '//*[contains(@class,"rich-text")]',
            '//*[contains(@class,"prose")]',
            '//main//*[contains(@class,"content")]',
            '//main',
        ]);

        $bodyHtml = '';
        if ($bodyNode !== null) {
            foreach (iterator_to_array($bodyNode->childNodes) as $child) {
                $name = strtolower($child->nodeName ?? '');
                if (in_array($name, ['script', 'style', 'nav', 'aside', 'footer', 'form'], true)) {
                    continue;
                }
                $bodyHtml .= $dom->saveHTML($child);
            }
        }

        $bodyText = trim(html_entity_decode(strip_tags($bodyHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $bodyText = preg_replace("/[ \t]+/", ' ', $bodyText) ?? $bodyText;
        $bodyText = preg_replace("/\n{3,}/", "\n\n", $bodyText) ?? $bodyText;

        // Softer threshold so short expert pages still import.
        if (mb_strlen($bodyText) < 120) {
            return null;
        }

        $paragraphs = preg_split('/\n+/', $bodyText) ?: [];
        $cleanParagraphs = [];
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (mb_strlen($paragraph) < 20) {
                continue;
            }
            $cleanParagraphs[] = '<p>'.e($paragraph).'</p>';
        }

        if ($cleanParagraphs === []) {
            $cleanParagraphs[] = '<p>'.e(Str::limit($bodyText, 4000)).'</p>';
        }

        $body = implode("\n", array_slice($cleanParagraphs, 0, 60));
        $excerpt = Str::limit(strip_tags($body), 220);

        return [
            'title' => Str::limit($title, 240),
            'excerpt' => $excerpt,
            'body' => $body,
            'image_url' => $image,
        ];
    }

    public function fingerprint(string $url, string $title, string $body): string
    {
        $normalized = mb_strtolower(trim($url.'|'.$title.'|'.preg_replace('/\s+/', ' ', strip_tags($body))));

        return hash('sha256', $normalized);
    }

    public function fetchHtml(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'de-CH,de;q=0.9,en;q=0.8',
            ])
                ->timeout(25)
                ->withOptions(['allow_redirects' => true, 'verify' => false])
                ->retry(1, 300)
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            if ($contentType !== '' && ! str_contains($contentType, 'html') && ! str_contains($contentType, 'xml') && ! str_contains($contentType, 'rss') && ! str_contains($contentType, 'atom')) {
                return null;
            }

            return $response->body();
        } catch (\Throwable $e) {
            Log::debug('Crawl fetch failed: '.$url.' — '.$e->getMessage());

            return null;
        }
    }

    /**
     * @return list<string>
     */
    private function fetchSitemapUrls(string $sitemapUrl, int $depth): array
    {
        if ($depth > 2) {
            return [];
        }

        $xml = $this->fetchHtml($sitemapUrl);
        if ($xml === null) {
            return [];
        }

        $urls = [];
        if (preg_match_all('/<loc>\s*(.*?)\s*<\/loc>/i', $xml, $matches)) {
            foreach ($matches[1] as $loc) {
                $url = html_entity_decode(trim($loc));
                $normalized = $this->normalizeUrl($url);
                if ($normalized === null) {
                    continue;
                }

                // Nested sitemap index
                if (str_contains(strtolower($normalized), 'sitemap') && str_ends_with(strtolower(parse_url($normalized, PHP_URL_PATH) ?: ''), '.xml')) {
                    foreach ($this->fetchSitemapUrls($normalized, $depth + 1) as $child) {
                        $urls[] = $child;
                    }
                    continue;
                }

                $urls[] = $normalized;
            }
        }

        return $urls;
    }

    /**
     * @return list<string>
     */
    private function fetchRssUrls(string $feedUrl): array
    {
        $xml = $this->fetchHtml($feedUrl);
        if ($xml === null) {
            return [];
        }

        $urls = [];

        // Atom <link href="...">
        if (preg_match_all('/<link[^>]+href=["\']([^"\']+)["\']/i', $xml, $matches)) {
            foreach ($matches[1] as $href) {
                $url = html_entity_decode(trim($href));
                if ($this->normalizeUrl($url) && str_starts_with($url, 'http')) {
                    $urls[] = $url;
                }
            }
        }

        if (preg_match_all('/<link[^>]*>(.*?)<\/link>/i', $xml, $matches)) {
            foreach ($matches[1] as $link) {
                $url = html_entity_decode(trim(strip_tags($link)));
                if ($this->normalizeUrl($url) && str_starts_with($url, 'http')) {
                    $urls[] = $url;
                }
            }
        }
        if (preg_match_all('/<guid[^>]*>(.*?)<\/guid>/i', $xml, $matches)) {
            foreach ($matches[1] as $guid) {
                $url = html_entity_decode(trim(strip_tags($guid)));
                if ($this->normalizeUrl($url) && str_starts_with($url, 'http')) {
                    $urls[] = $url;
                }
            }
        }

        return $urls;
    }

    /**
     * @return list<string>
     */
    private function extractLinks(string $html, string $baseUrl): array
    {
        $urls = [];
        if (! preg_match_all('/href=["\']([^"\']+)["\']/i', $html, $matches)) {
            return [];
        }

        foreach ($matches[1] as $href) {
            $absolute = $this->absolutizeUrl(html_entity_decode($href), $baseUrl);
            if ($absolute !== null) {
                $urls[] = $absolute;
            }
        }

        return $urls;
    }

    private function scoreArticleUrl(string $url, string $startUrl): int
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        $path = rtrim($path, '/') ?: '/';

        if ($path === '/' || $path === '') {
            return 0;
        }

        // Skip assets
        if (preg_match('/\.(pdf|jpe?g|png|gif|webp|svg|css|js|zip|docx?)$/i', $path)) {
            return 0;
        }

        $score = 0;
        $boost = [
            'blog' => 40, 'news' => 40, 'artikel' => 35, 'article' => 35,
            'wissen' => 30, 'aktuell' => 30, 'insights' => 30, 'post' => 28,
            'magazin' => 28, 'beitrag' => 28, 'presse' => 22, 'media' => 15,
            'nachfolge' => 18, 'unternehmen' => 10, 'fach' => 12,
        ];

        foreach ($boost as $needle => $points) {
            if (str_contains($path, $needle)) {
                $score += $points;
            }
        }

        if (preg_match('#/\d{4}/\d{1,2}/#', $path)) {
            $score += 35;
        }

        $segments = array_values(array_filter(explode('/', $path)));
        $last = end($segments) ?: '';

        // Meaningful slug
        if (strlen($last) > 16 && str_contains($last, '-')) {
            $score += 18;
        } elseif (strlen($last) > 10) {
            $score += 8;
        }

        if (count($segments) >= 2) {
            $score += 6;
        }
        if (count($segments) >= 3) {
            $score += 4;
        }

        // Prefer pages under same path prefix as start URL (e.g. /blog/*)
        $startPath = rtrim(strtolower((string) parse_url($startUrl, PHP_URL_PATH)), '/') ?: '';
        if ($startPath !== '' && $startPath !== '/' && str_starts_with($path, $startPath.'/') && $path !== $startPath) {
            $score += 25;
        }

        // Listing hubs themselves score low unless they're the only option
        $hubExact = ['/blog', '/news', '/aktuelles', '/wissen', '/insights', '/magazin', '/artikel', '/beitraege'];
        if (in_array($path, $hubExact, true)) {
            $score = min($score, 4);
        }

        return $score;
    }

    private function isDeniedPath(string $url): bool
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        $deny = [
            '/login', '/logout', '/cart', '/checkout', '/wp-admin', '/wp-login',
            '/tag/', '/category/', '/author/', '/page/', '/suche', '/search',
            '/kontakt', '/contact', '/impressum', '/datenschutz', '/privacy',
            '/agb', '/cookie', '/cdn-cgi', '/wp-json', '/feed', '/rss',
            '/warenkorb', '/account', '/mein-konto',
        ];

        foreach ($deny as $needle) {
            if (str_contains($path, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function sameSite(string $url, string $baseHostKey): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $this->hostKey($host) === $baseHostKey;
    }

    private function hostKey(string $host): string
    {
        $host = strtolower($host);
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }

    private function normalizeUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $url = trim($url);
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($url);
        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $normalized = $scheme.'://'.$parts['host'];
        if (isset($parts['port'])) {
            $normalized .= ':'.$parts['port'];
        }

        $path = $parts['path'] ?? '/';
        // Drop trailing index noise
        $path = preg_replace('#/index\.html?$#i', '/', $path) ?: $path;
        $normalized .= $path === '' ? '/' : $path;

        if (! empty($parts['query'])) {
            // Keep only harmless query keys (some CMSs need ?p=)
            parse_str($parts['query'], $query);
            $keep = array_intersect_key($query, array_flip(['p', 'page_id', 'id']));
            if ($keep !== []) {
                $normalized .= '?'.http_build_query($keep);
            }
        }

        return rtrim($normalized, '#');
    }

    private function absolutizeUrl(string $href, string $baseUrl): ?string
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:') || str_starts_with($href, 'data:')) {
            return null;
        }

        if (str_starts_with($href, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';

            return $this->normalizeUrl($scheme.':'.$href);
        }

        if (preg_match('#^https?://#i', $href)) {
            return $this->normalizeUrl($href);
        }

        $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($baseUrl, PHP_URL_HOST);
        $basePath = parse_url($baseUrl, PHP_URL_PATH) ?: '/';

        if ($host === null) {
            return null;
        }

        if (str_starts_with($href, '/')) {
            return $this->normalizeUrl($scheme.'://'.$host.$href);
        }

        $dir = preg_replace('#/[^/]*$#', '/', $basePath) ?: '/';

        return $this->normalizeUrl($scheme.'://'.$host.$dir.$href);
    }

    /**
     * @param  list<string>  $queries
     */
    private function firstText(DOMXPath $xpath, array $queries): ?string
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes !== false && $nodes->length > 0) {
                $value = trim($nodes->item(0)->textContent ?? ($nodes->item(0)->nodeValue ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $queries
     */
    private function firstNode(DOMXPath $xpath, array $queries): ?\DOMNode
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes !== false && $nodes->length > 0) {
                return $nodes->item(0);
            }
        }

        return null;
    }
}
