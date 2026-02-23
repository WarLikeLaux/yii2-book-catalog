<?php

declare(strict_types=1);

namespace app\infrastructure\persistence;

final class DatabaseErrorCode
{
    public const MYSQL_DUPLICATE_ENTRY = 1062;
    public const PGSQL_UNIQUE_VIOLATION = '23505';
    public const SQLITE_CONSTRAINT_UNIQUE = 19;

    public static function isDuplicate(string|int|null $code): bool
    {
        if ($code === null) {
            return false;
        }

        return in_array($code, [
            self::MYSQL_DUPLICATE_ENTRY,
            self::PGSQL_UNIQUE_VIOLATION,
            self::SQLITE_CONSTRAINT_UNIQUE,
            (string)self::MYSQL_DUPLICATE_ENTRY,
        ], true);
    }
}
