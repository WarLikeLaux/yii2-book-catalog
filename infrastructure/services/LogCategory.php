<?php

declare(strict_types=1);

namespace app\infrastructure\services;

use PHPUnit\Framework\Attributes\CodeCoverageIgnore;

final class LogCategory
{
    public const string APPLICATION = 'application';
    public const string SMS = 'sms';

    #[CodeCoverageIgnore]
    private function __construct()
    {
    }
}
