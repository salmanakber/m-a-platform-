<?php

namespace App\Support;

final class EventStatus
{
    public const DRAFT = 'draft';

    public const PUBLISHED = 'published';

    public const BLOCKED = 'blocked';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::DRAFT, self::PUBLISHED, self::BLOCKED];
    }
}
