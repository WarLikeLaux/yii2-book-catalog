<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\common\dto\IdempotencyRecordDto;
use app\application\common\IdempotencyKeyStatus;
use app\application\ports\IdempotencyInterface;
use app\infrastructure\persistence\IdempotencyKey;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

final readonly class IdempotencyRepository implements IdempotencyInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private ClockInterface $clock,
    ) {
    }

    public function getRecord(string $key): IdempotencyRecordDto|null
    {
        /** @var IdempotencyKey|null $model */
        $model = IdempotencyKey::find()
            ->where(['idempotency_key' => $key])
            ->andWhere(['>', 'expires_at', $this->clock->now()->getTimestamp()])
            ->one();

        if ($model === null) {
            return null;
        }

        $status = IdempotencyKeyStatus::tryFrom($model->status);

        if (!$status instanceof IdempotencyKeyStatus) {
            return null;
        }

        if ($status === IdempotencyKeyStatus::Started) {
            return new IdempotencyRecordDto($status, null, [], null);
        }

        $body = $this->normalizeResponseBody($model->response_body);
        $decoded = is_string($body) ? json_decode($body, true) : null;
        $data = is_array($decoded) ? $decoded : [];
        $redirectUrl = $data['redirect_url'] ?? null;

        return new IdempotencyRecordDto(
            $status,
            $model->status_code,
            $data,
            is_string($redirectUrl) ? $redirectUrl : null,
        );
    }

    public function saveStarted(string $key, int $ttl): bool
    {
        $model = new IdempotencyKey();
        $model->idempotency_key = $key;
        $model->status = IdempotencyKeyStatus::Started->value;
        $now = $this->clock->now()->getTimestamp();
        $model->created_at = $now;
        $model->expires_at = $now + $ttl;

        if ($model->save()) {
            return true;
        }

        $this->logger->error('Failed to save idempotency key: ' . json_encode($model->getErrors()));
        return false;
    }

    public function saveResponse(string $key, int $statusCode, string $body, int $ttl): void
    {
        /** @var IdempotencyKey|null $model */
        $model = IdempotencyKey::find()
            ->where(['idempotency_key' => $key])
            ->one();

        if ($model instanceof IdempotencyKey && $model->status === IdempotencyKeyStatus::Finished->value) {
            return;
        }

        $now = $this->clock->now()->getTimestamp();

        if (!($model instanceof IdempotencyKey)) {
            $model = new IdempotencyKey();
            $model->idempotency_key = $key;
            $model->created_at = $now;
        }

        $model->status = IdempotencyKeyStatus::Finished->value;
        $model->status_code = $statusCode;
        $model->response_body = $body;
        $model->expires_at = $now + $ttl;

        if ($model->save()) {
            return;
        }

        $this->logger->error('Failed to save idempotency key: ' . json_encode($model->getErrors()));
    }

    private function normalizeResponseBody(mixed $responseBody): string|null
    {
        if (is_string($responseBody) || $responseBody === null) {
            return $responseBody;
        }

        if (!is_resource($responseBody)) {
            return null;
        }

        $content = stream_get_contents($responseBody);

        if ($content === false) {
            return null;
        }

        return $content;
    }
}
