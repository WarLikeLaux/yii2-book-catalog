<?php

declare(strict_types=1);

namespace app\infrastructure\persistence;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $phone
 * @property int $author_id
 * @property int $created_at
 * @property Author $author
 */
final class Subscription extends ActiveRecord
{
    public static function create(string $phone, int $authorId): self
    {
        $subscription = new self();
        $subscription->phone = $phone;
        $subscription->author_id = $authorId;
        return $subscription;
    }

    public static function tableName(): string
    {
        return 'subscriptions';
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['phone', 'author_id'], 'required'],
            [['author_id'], 'integer'],
            [['phone'], 'string', 'max' => 20],
            [['phone', 'author_id'], 'unique', 'targetAttribute' => ['phone', 'author_id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'phone' => 'Телефон',
            'author_id' => 'Автор',
            'created_at' => 'Создано',
        ];
    }

    public function getAuthor(): ActiveQuery
    {
        return $this->hasOne(Author::class, ['id' => 'author_id']);
    }
}
