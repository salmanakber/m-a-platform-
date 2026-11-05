<?php

namespace App\Support;

final class UrlNormalizer
{
    /**
     * Accept domains like "www.example.ch" and return a valid URL, or null if empty.
     */
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Already has a scheme
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $value)) {
            return $value;
        }

        // Protocol-relative
        if (str_starts_with($value, '//')) {
            return 'https:'.$value;
        }

        return 'https://'.$value;
    }

    /** Merge normalized URL fields into the request before validation. */
    public static function mergeRequest(\Illuminate\Http\Request $request, array $keys): void
    {
        $payload = [];
        foreach ($keys as $key) {
            if ($request->exists($key)) {
                $payload[$key] = self::normalize($request->input($key));
            }
        }
        if ($payload !== []) {
            $request->merge($payload);
        }
    }
}
