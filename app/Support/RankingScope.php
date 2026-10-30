<?php

namespace App\Support;

final class RankingScope
{
    public const HERO_MATCH = 'hero_match';

    public const NATIONAL_TOP5 = 'national_top5';

    /** Stored key kept for compatibility; UI shows Top 5. */
    public const CANTON_TOP3 = 'canton_top3';

    public const GROUP_TOP = 'group_top';

    public const ARTICLE_TOP = 'article_top';

    public const EVENT_TOP = 'event_top';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::HERO_MATCH => 'Banner Top Match',
            self::NATIONAL_TOP5 => 'Top 5 Schweiz',
            self::CANTON_TOP3 => 'Top 5 Kanton',
            self::GROUP_TOP => 'Top Fachgruppe',
            self::ARTICLE_TOP => 'Top 3 Fachbeiträge',
            self::EVENT_TOP => 'Top Events',
        ];
    }

    /** Short help text for admin UI. */
    public static function help(string $key): string
    {
        return match ($key) {
            self::HERO_MATCH => 'Bis zu 3 Experten im Startseiten-Banner (rechts, Slider).',
            self::NATIONAL_TOP5 => 'Bis zu 5 Experten für die nationale Rangliste.',
            self::CANTON_TOP3 => 'Bis zu 5 Experten pro Kanton.',
            self::GROUP_TOP => 'Bis zu 5 Experten für M&A, Juristen oder Marktplätze.',
            self::ARTICLE_TOP => 'Bis zu 3 Fachbeiträge global oder je Fachbereich-Gruppe.',
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
            self::NATIONAL_TOP5, self::CANTON_TOP3, self::GROUP_TOP => 5,
            self::ARTICLE_TOP, self::EVENT_TOP => 3,
            default => 1,
        };
    }
}
