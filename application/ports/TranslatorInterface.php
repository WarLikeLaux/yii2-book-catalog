<?php

declare(strict_types=1);

namespace app\application\ports;

interface TranslatorInterface
{
    public function translate(string $category, string $message, array $params = []): string;
}
