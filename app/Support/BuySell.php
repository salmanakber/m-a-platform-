<?php

namespace App\Support;

final class BuySell
{
    public const BUY = 'buy';

    public const SELL = 'sell';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::BUY, self::SELL];
    }
}
