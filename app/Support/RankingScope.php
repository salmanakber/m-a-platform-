<?php

namespace App\Support;

final class RankingScope
{
    public const HERO_MATCH = 'hero_match';

    public const NATIONAL_TOP5 = 'national_top5';

    public const CANTON_TOP3 = 'canton_top3';

    public const GROUP_TOP = 'group_top';

    public const ARTICLE_TOP = 'article_top';

    public const EVENT_TOP = 'event_top';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::HERO_MATCH => 'Hero Top Match (Startseite)',
            self::NATIONAL_TOP5 => 'Top 5 Experten Schweiz',
            self::CANTON_TOP3 => 'Top 3 je Kanton',
            self::GROUP_TOP => 'Top je Fachgruppe (M&A / Juristen / Marktplätze)',
            self::ARTICLE_TOP => 'Top 3 Fachbeiträge',
            self::EVENT_TOP => 'Top Events (Startseite)',
        ];
    }

    /** @return list<string> */
    public static function all(): array
    {
        return array_keys(self::labels());
    }

    public static function label(string $key): string
    {
        return self::labels()[$key] ?? $key;
    }

    public static function maxPosition(string $scope): int
    {
        return match ($scope) {
            self::NATIONAL_TOP5 => 5,
            self::CANTON_TOP3, self::ARTICLE_TOP => 3,
            self::GROUP_TOP => 5,
            default => 1,
        };
    }
}
