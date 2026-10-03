<?php

namespace App\Support;

final class PromotionStatus
{
    public const PENDING = 'pending';

    public const ACTIVE = 'active';

    public const EXPIRED = 'expired';

    public const CANCELLED = 'cancelled';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::ACTIVE,
            self::EXPIRED,
            self::CANCELLED,
        ];
    }
}
