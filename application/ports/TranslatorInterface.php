<?php

declare(strict_types=1);

namespace app\application\ports;

interface TranslatorInterface
{
    /**
     * @param array<string, mixed> $params
     */
    public function translate(string $category, string $message, array $params = []): string;
}
