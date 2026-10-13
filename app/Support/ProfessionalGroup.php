<?php

namespace App\Support;

final class ProfessionalGroup
{
    public const MA_SPECIALIST = 'ma_specialist';

    public const JURIST = 'jurist';

    public const OTHER = 'other';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::MA_SPECIALIST => 'M&A Specialists',
            self::JURIST => 'Juristen',
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
}
