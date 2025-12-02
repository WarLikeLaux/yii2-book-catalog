<?php

declare(strict_types=1);

namespace app\models\forms;

use yii\base\Model;

final class SubscriptionForm extends Model
{
    public string $phone = '';
    public int $authorId = 0;

    public function rules(): array
    {
        return [
            [['phone', 'authorId'], 'required'],
            [['authorId'], 'integer'],
            [['phone'], 'string', 'max' => 20],
            [['phone'], 'match', 'pattern' => '/^\+?[0-9]{10,15}$/', 'message' => 'Неверный формат телефона'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'phone' => 'Телефон',
            'authorId' => 'Автор',
        ];
    }
}

