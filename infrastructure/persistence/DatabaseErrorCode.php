<?php

declare(strict_types=1);

namespace app\infrastructure\persistence;

final class DatabaseErrorCode
{
    public const MYSQL_DUPLICATE_ENTRY = 1062;
    public const MYSQL_FOREIGN_KEY_VIOLATION = 1452;

    public const PGSQL_UNIQUE_VIOLATION = '23505';
    public const PGSQL_FOREIGN_KEY_VIOLATION = '23503';

    public const SQLITE_CONSTRAINT_UNIQUE = 19;
    public const SQLITE_CONSTRAINT_FOREIGNKEY = 787;

    /**
     * @param string|int|null $code
     */
    public static function isDuplicate($code): bool
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
