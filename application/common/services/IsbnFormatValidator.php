<?php

declare(strict_types=1);

namespace app\application\common\services;

use app\domain\values\Isbn;

final readonly class IsbnFormatValidator
{
    public function isValid(string $value): bool
    {
        return Isbn::isValid($value);
    }
}
