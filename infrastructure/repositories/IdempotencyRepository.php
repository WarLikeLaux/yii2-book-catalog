<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\common\IdempotencyKeyStatus;
use app\application\ports\IdempotencyInterface;
use app\infrastructure\persistence\IdempotencyKey;
use Psr\Log\LoggerInterface;

final readonly class IdempotencyRepository implements IdempotencyInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /** @return array{status: string, status_code: int|null, body: string|null}|null */
    public function getRecord(string $key): array|null
    {
        /** @var IdempotencyKey|null $model */
        $model = IdempotencyKey::find()
            ->where(['idempotency_key' => $key])
            ->andWhere(['>', 'expires_at', time()])
            ->one();

        if ($model === null) {
            return null;
        }

        return [
            'status' => $model->status,
            'status_code' => $model->status_code,
            'body' => $this->normalizeResponseBody($model->response_body),
        ];
    }

    public function saveStarted(string $key, int $ttl): bool
    {
        $model = new IdempotencyKey();
        $model->idempotency_key = $key;
        $model->status = IdempotencyKeyStatus::Started->value;
        $model->created_at = time();
        $model->expires_at = time() + $ttl;

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

        if (!($model instanceof IdempotencyKey)) {
            $model = new IdempotencyKey();
            $model->idempotency_key = $key;
            $model->created_at = time();
        }

        $model->status = IdempotencyKeyStatus::Finished->value;
        $model->status_code = $statusCode;
        $model->response_body = $body;
        $model->expires_at = time() + $ttl;

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
