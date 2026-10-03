<?php

namespace App\Support;

final class Role
{
    public const ADMIN = 'admin';

    public const EXPERT = 'expert';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::ADMIN, self::EXPERT];
    }
}
