<?php

namespace App\Support;

final class ArticleStatus
{
    public const PUBLISHED = 'published';

    public const BLOCKED = 'blocked';

    public const DRAFT = 'draft';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::PUBLISHED,
            self::BLOCKED,
            self::DRAFT,
        ];
    }
}
