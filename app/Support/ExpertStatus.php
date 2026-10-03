<?php

namespace App\Support;

final class ExpertStatus
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const DISABLED = 'disabled';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::APPROVED,
            self::REJECTED,
            self::DISABLED,
        ];
    }
}
