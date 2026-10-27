<?php

namespace App\Support;

final class ProfessionalGroup
{
    public const MA_SPECIALIST = 'ma_specialist';

    public const JURIST = 'jurist';

    public const MARKTPLATZ = 'marktplatz';

    public const OTHER = 'other';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::MA_SPECIALIST => 'M&A-Spezialisten',
            self::JURIST => 'Juristen',
            self::MARKTPLATZ => 'Marktplätze',
            self::OTHER => 'Weitere',
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

    /**
     * Groups shown on the Switzerland map (Marktplätze are card/ranking only).
     *
     * @return array<string, string>
     */
    public static function mapLabels(): array
    {
        return array_filter(
            self::labels(),
            fn (string $key) => $key !== self::MARKTPLATZ,
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Map pin / UI colors per professional group.
     *
     * @return array<string, string>
     */
    public static function colors(): array
    {
        return [
            self::MA_SPECIALIST => '#009DE1', // blue
            self::JURIST => '#0B6E4F', // green
            self::MARKTPLATZ => '#C2410C', // terracotta
            self::OTHER => '#64748B', // slate
        ];
    }

    public static function color(string $key): string
    {
        return self::colors()[$key] ?? self::colors()[self::MA_SPECIALIST];
    }
}
