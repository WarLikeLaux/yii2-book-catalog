<?php

declare(strict_types=1);

namespace app\application\common;

use app\application\common\dto\IdempotencyResponseDto;
use app\application\ports\IdempotencyInterface;

final readonly class IdempotencyService implements IdempotencyServiceInterface
{
    public function __construct(
        private IdempotencyInterface $repository
    ) {
    }

    public function getResponse(string $key): IdempotencyResponseDto|null
    {
        $saved = $this->repository->getResponse($key);
        if ($saved === null) {
            return null;
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($saved['body'], true);
        $redirectUrl = $data['redirect_url'] ?? null;

        return new IdempotencyResponseDto(
            $saved['status_code'],
            $data,
            is_string($redirectUrl) ? $redirectUrl : null
        );
    }

    public function saveResponse(
        string $key,
        int $statusCode,
        mixed $result,
        string|null $redirectUrl,
        int $ttl
    ): void {
        $dataToCache = $redirectUrl !== null
            ? ['redirect_url' => $redirectUrl]
            : (is_array($result) ? $result : []);

        $this->repository->saveResponse(
            $key,
            $statusCode,
            (string)json_encode($dataToCache),
            $ttl
        );
    }
}
