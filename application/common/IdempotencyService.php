<?php

declare(strict_types=1);

namespace app\application\common;

use app\application\common\dto\IdempotencyRecordDto;
use app\application\ports\IdempotencyInterface;
use app\application\ports\MutexInterface;
use JsonSerializable;

final readonly class IdempotencyService implements IdempotencyServiceInterface
{
    private const string LOCK_PREFIX = 'idempotency:';

    public function __construct(
        private IdempotencyInterface $repository,
        private MutexInterface $mutex
    ) {
    }

    public function acquireLock(string $key, int $timeout = 0): bool
    {
        return $this->mutex->acquire(self::LOCK_PREFIX . $key, $timeout);
    }

    public function releaseLock(string $key): void
    {
        $this->mutex->release(self::LOCK_PREFIX . $key);
    }

    public function startRequest(string $key, int $ttl): bool
    {
        return $this->repository->saveStarted($key, $ttl);
    }

    public function getRecord(string $key): IdempotencyRecordDto|null
    {
        $saved = $this->repository->getRecord($key);

        if ($saved === null) {
            return null;
        }

        $status = IdempotencyKeyStatus::tryFrom($saved['status']);

        if (!$status instanceof IdempotencyKeyStatus) {
            return null;
        }

        if ($status === IdempotencyKeyStatus::Started) {
            return new IdempotencyRecordDto(
                $status,
                null,
                [],
                null
            );
        }

        $body = $saved['body'];
        $decoded = is_string($body) ? json_decode($body, true) : null;
        $data = is_array($decoded) ? $decoded : [];
        $redirectUrl = $data['redirect_url'] ?? null;

        return new IdempotencyRecordDto(
            $status,
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
        $dataToCache = $this->serializeResult($result, $redirectUrl);

        $this->repository->saveResponse(
            $key,
            $statusCode,
            (string)json_encode($dataToCache),
            $ttl
        );
    }

    /** @return array<string, mixed> */
    private function serializeResult(mixed $result, string|null $redirectUrl): array
    {
        if ($redirectUrl !== null) {
            return ['redirect_url' => $redirectUrl];
        }

        if ($result instanceof JsonSerializable) {
            /** @var array<string, mixed> $serialized */
            $serialized = $result->jsonSerialize();
            return $serialized;
        }

        if (is_array($result)) {
            /** @var array<string, mixed> $result */
            return $result;
        }

        return [];
    }
}
