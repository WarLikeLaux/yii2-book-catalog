<?php

declare(strict_types=1);

namespace app\presentation\common\dto;

final readonly class ApiResponse
{
    /**
     * @param array<string, mixed> $errors
     */
    public function __construct(
        public bool $success,
        public string $message = '',
        public mixed $data = null,
        public array $errors = [],
    ) {
    }

    public static function success(string $message, mixed $data = null): self
    {
        return new self(true, $message, $data);
    }

    /**
     * @param array<string, mixed> $errors
     */
    public static function failure(string $message, array $errors = []): self
    {
        return new self(false, $message, null, $errors);
    }
}
