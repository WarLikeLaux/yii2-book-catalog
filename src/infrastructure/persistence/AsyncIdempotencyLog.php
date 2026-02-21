<?php

declare(strict_types=1);

namespace app\infrastructure\persistence;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $idempotency_key
 * @property int $created_at
 */
final class AsyncIdempotencyLog extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%async_idempotency_log}}';
    }

    /** @return array<array<int|string, mixed>> */
    public function rules(): array
    {
        return [
            [['idempotency_key', 'created_at'], 'required'],
            [['created_at'], 'integer'],
            [['idempotency_key'], 'string', 'max' => 128],
            [['idempotency_key'], 'unique'],
        ];
    }
}
