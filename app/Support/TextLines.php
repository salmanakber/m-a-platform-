<?php

namespace App\Support;

final class TextLines
{
    /**
     * @return list<string>
     */
    public static function fromInput(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $parts = preg_split('/[\r\n]+/', $value) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $line = trim((string) $part);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return array_values($out);
    }

    /**
     * @param  list<string>|null  $lines
     */
    public static function toTextarea(?array $lines): string
    {
        if ($lines === null || $lines === []) {
            return '';
        }

        return implode("\n", array_map('strval', $lines));
    }
}
