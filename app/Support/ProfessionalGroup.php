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
            self::MA_SPECIALIST => 'M&A Specialists',
            self::JURIST => 'Juristen',
            self::MARKTPLATZ => 'Marktplätze',
            self::OTHER => 'Others',
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
