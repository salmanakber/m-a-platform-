<?php

namespace App\Support;

final class InvoicePaymentStatus
{
    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const CANCELLED = 'cancelled';

    public const OVERDUE = 'overdue';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::PAID,
            self::CANCELLED,
            self::OVERDUE,
        ];
    }
}
