<?php

namespace App\Support;

final class LeadStatus
{
    public const STATUS_NEW = 'new';

    public const CONTACTED = 'contacted';

    public const IN_PROGRESS = 'in_progress';

    public const CLOSED = 'closed';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::STATUS_NEW,
            self::CONTACTED,
            self::IN_PROGRESS,
            self::CLOSED,
        ];
    }
}
