<?php

declare(strict_types=1);

namespace app\application\common\dto;

use app\application\common\IdempotencyKeyStatus;

final readonly class IdempotencyRecordDto
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public IdempotencyKeyStatus $status,
        public int|null $statusCode,
        public array $data,
        public string|null $redirectUrl
    ) {
    }

    public function isFinished(): bool
    {
        return $this->status === IdempotencyKeyStatus::Finished;
    }
}
