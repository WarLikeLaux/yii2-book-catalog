<?php

declare(strict_types=1);

namespace app\infrastructure\services\sms;

final class PhoneMasker
{
    private const int VISIBLE_PREFIX_LENGTH = 2;
    private const int VISIBLE_SUFFIX_LENGTH = 2;
    private const int MAX_FULL_MASK_LENGTH = 4;

    public static function mask(string $phone): string
    {
        $len = strlen($phone);

        if ($len <= self::MAX_FULL_MASK_LENGTH) {
            return str_repeat('*', $len);
        }

        return substr($phone, 0, self::VISIBLE_PREFIX_LENGTH)
        . str_repeat('*', $len - self::VISIBLE_PREFIX_LENGTH - self::VISIBLE_SUFFIX_LENGTH)
        . substr($phone, -self::VISIBLE_SUFFIX_LENGTH);
    }
}
