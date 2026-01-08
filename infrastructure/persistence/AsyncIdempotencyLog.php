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
    /**
     * Database table name for this ActiveRecord.
     *
     * @return string The table name including the Yii DB prefix placeholder, e.g. '{{%async_idempotency_log}}'.
     */
    public static function tableName(): string
    {
        return '{{%async_idempotency_log}}';
    }

    /**
     * Validation rules for the model's attributes.
     *
     * @return array<array<int|string, mixed>> Array of validation rule definitions in Yii2 format (each entry specifies attributes and their validators/options).
     */
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