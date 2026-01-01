<?php

declare(strict_types=1);

namespace app\infrastructure\persistence;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $idempotency_key
 * @property string $status
 * @property int|null $status_code
 * @property string|null $response_body
 * @property int $created_at
 * @property int $expires_at
 */
final class IdempotencyKey extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%idempotency_keys}}';
    }

    public function rules(): array
    {
        return [
            [['idempotency_key', 'status', 'created_at', 'expires_at'], 'required'],
            [['status_code', 'created_at', 'expires_at'], 'integer'],
            [['response_body'], 'string'],
            [['idempotency_key'], 'string', 'max' => 36],
            [['idempotency_key'], 'unique'],
            [['status'], 'string', 'max' => 20],
        ];
    }
}
