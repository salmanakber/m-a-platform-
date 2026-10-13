<?php

namespace App\Services\Logo;

use App\Models\Expert;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class LogoDiscoveryService
{
    /**
     * Discover and store a company logo from the expert website when none is locked in.
     */
    public function discoverForExpert(Expert $expert): ?string
    {
        if ($expert->logo_locked) {
            return $expert->logo_path;
        }

        $base = $expert->website_crawl_url ?: $expert->website;
        if (blank($base)) {
            return $expert->logo_path;
        }

        $candidates = $this->candidateUrls((string) $base);
        foreach ($candidates as $url) {
            $stored = $this->storeRemoteImage($url);
            if ($stored) {
                if ($expert->logo_path && $expert->logo_path !== $stored) {
                    Storage::disk('public')->delete($expert->logo_path);
                }
                $expert->forceFill(['logo_path' => $stored])->save();

                return $stored;
            }
        }

        return $expert->logo_path;
    }

    /**
     * Store a logo from an explicit URL (e.g. import column). Respects logo_locked.
     */
    public function storeFromUrl(Expert $expert, string $url): ?string
    {
        if ($expert->logo_locked) {
            return $expert->logo_path;
        }

        $stored = $this->storeRemoteImage($url);
        if (! $stored) {
            return $expert->logo_path;
        }

        if ($expert->logo_path && $expert->logo_path !== $stored) {
            Storage::disk('public')->delete($expert->logo_path);
        }
        $expert->forceFill(['logo_path' => $stored])->save();

        return $stored;
    }

    /**
     * @return list<string>
     */
    private function candidateUrls(string $baseUrl): array
    {
        $baseUrl = trim($baseUrl);
        if (! preg_match('#^https?://#i', $baseUrl)) {
            $baseUrl = 'https://'.$baseUrl;
        }

        $parts = parse_url($baseUrl);
        if (! is_array($parts) || empty($parts['host'])) {
            return [];
        }

        $origin = ($parts['scheme'] ?? 'https').'://'.$parts['host'];
        $html = null;

        try {
            $response = Http::timeout(12)
                ->withHeaders(['User-Agent' => 'NachfolgeExpertenBot/1.0'])
                ->get($origin);
            if ($response->successful()) {
                $html = $response->body();
            }
        } catch (Throwable) {
            $html = null;
        }

        $found = [];
        if (is_string($html) && $html !== '') {
            foreach ([
                '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i',
                '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i',
                '/<link[^>]+rel=["\'](?:apple-touch-icon|icon|shortcut icon)["\'][^>]+href=["\']([^"\']+)["\']/i',
                '/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\'](?:apple-touch-icon|icon|shortcut icon)["\']/i',
                '/<img[^>]+class=["\'][^"\']*logo[^"\']*["\'][^>]+src=["\']([^"\']+)["\']/i',
            ] as $pattern) {
                if (preg_match($pattern, $html, $m)) {
                    $abs = $this->absolutize($origin, $m[1]);
                    if ($abs) {
                        $found[] = $abs;
                    }
                }
            }
        }

        $found[] = $origin.'/apple-touch-icon.png';
        $found[] = $origin.'/favicon-32x32.png';
        $found[] = $origin.'/favicon.png';
        $found[] = $origin.'/logo.png';

        return array_values(array_unique(array_filter($found)));
    }

    private function absolutize(string $origin, string $url): ?string
    {
        $url = html_entity_decode(trim($url));
        if ($url === '' || str_starts_with($url, 'data:')) {
            return null;
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }
        if (str_starts_with($url, '/')) {
            return rtrim($origin, '/').$url;
        }

        return rtrim($origin, '/').'/'.$url;
    }

    private function storeRemoteImage(string $url): ?string
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'NachfolgeExpertenBot/1.0'])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $bytes = $response->body();
            if (strlen($bytes) < 200 || strlen($bytes) > 4_000_000) {
                return null;
            }

            $mime = (string) ($response->header('Content-Type') ?: '');
            $ext = match (true) {
                str_contains($mime, 'png') => 'png',
                str_contains($mime, 'webp') => 'webp',
                str_contains($mime, 'gif') => 'gif',
                str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => 'jpg',
                default => null,
            };

            if ($ext === null) {
                $pathExt = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
                $ext = in_array($pathExt, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)
                    ? ($pathExt === 'jpeg' ? 'jpg' : $pathExt)
                    : null;
            }

            if ($ext === null) {
                return null;
            }

            Storage::disk('public')->makeDirectory('experts/logos');
            $path = 'experts/logos/'.Str::random(24).'.'.$ext;
            Storage::disk('public')->put($path, $bytes);

            return $path;
        } catch (Throwable) {
            return null;
        }
    }
}
