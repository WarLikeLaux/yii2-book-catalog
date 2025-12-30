<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\ports\IdempotencyInterface;
use app\infrastructure\persistence\IdempotencyKey;
use Yii;

final class IdempotencyRepository implements IdempotencyInterface
{
    /** @return array{status_code: int, body: string}|null */
    public function getResponse(string $key): array|null
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
            'status_code' => $model->status_code,
            'body' => $model->response_body,
        ];
    }

    public function saveResponse(string $key, int $statusCode, string $body, int $ttl): void
    {
        $model = new IdempotencyKey();
        $model->idempotency_key = $key;
        $model->status_code = $statusCode;
        $model->response_body = $body;
        $model->created_at = time();
        $model->expires_at = time() + $ttl;

        if ($model->save()) {
            return;
        }

        Yii::error('Failed to save idempotency key: ' . json_encode($model->getErrors()));
    }
}
