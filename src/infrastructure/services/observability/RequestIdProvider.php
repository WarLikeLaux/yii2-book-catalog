<?php

declare(strict_types=1);

namespace app\infrastructure\services\observability;

final class RequestIdProvider
{
    private static string|null $requestId = null;

    public static function get(): string
    {
        if (self::$requestId === null) {
            self::$requestId = self::generate();
        }

        return self::$requestId;
    }

    public static function reset(): void
    {
        self::$requestId = null;
    }

    private static function generate(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
