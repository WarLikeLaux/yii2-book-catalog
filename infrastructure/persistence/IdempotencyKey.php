<?php

declare(strict_types=1);

namespace app\infrastructure\persistence;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $idempotency_key
 * @property int $status_code
 * @property string $response_body
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
            [['idempotency_key', 'status_code', 'response_body', 'created_at', 'expires_at'], 'required'],
            [['status_code', 'created_at', 'expires_at'], 'integer'],
            [['idempotency_key'], 'string', 'max' => 36],
            [['idempotency_key'], 'unique'],
        ];
    }
}
