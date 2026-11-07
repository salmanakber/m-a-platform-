<?php

namespace App\Support;

final class RankingScope
{
    public const HERO_MATCH = 'hero_match';

    public const NATIONAL_TOP5 = 'national_top5';

    /** Stored key kept for compatibility; UI shows Top 6. */
    public const CANTON_TOP3 = 'canton_top3';

    public const GROUP_TOP = 'group_top';

    public const ARTICLE_TOP = 'article_top';

    public const EVENT_TOP = 'event_top';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::HERO_MATCH => 'Banner Top Match',
            self::NATIONAL_TOP5 => 'Top 6 Schweiz',
            self::CANTON_TOP3 => 'Top 6 Kanton',
            self::GROUP_TOP => 'Top 6 Fachgruppe',
            self::ARTICLE_TOP => 'Top 6 Fachbeiträge',
            self::EVENT_TOP => 'Top Events',
        ];
    }

    /** Short help text for admin UI. */
    public static function help(string $key): string
    {
        return match ($key) {
            self::HERO_MATCH => 'Bis zu 3 Experten im Startseiten-Banner (rechts, Slider).',
            self::NATIONAL_TOP5 => 'Bis zu 6 Experten für die nationale Rangliste (Startseite Berater). Leere Plätze erscheinen als «OFFEN».',
            self::CANTON_TOP3 => 'Bis zu 6 Experten pro Kanton. Leere Plätze erscheinen als «OFFEN».',
            self::GROUP_TOP => 'Bis zu 6 Experten für M&A, Juristen, Treuhand, Ad Interim oder Marktplätze.',
            self::ARTICLE_TOP => 'Bis zu 6 Fachbeiträge je Fachbereich-Gruppe.',
            self::EVENT_TOP => 'Hervorgehobene Events auf der Startseite.',
            default => '',
        };
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
            self::HERO_MATCH => 3,
            self::NATIONAL_TOP5, self::CANTON_TOP3, self::GROUP_TOP, self::ARTICLE_TOP => 6,
            self::EVENT_TOP => 3,
            default => 1,
        };
    }
}
