<?php

declare(strict_types=1);

namespace app\models\forms;

use app\models\Author;
use yii\base\Model;

final class AuthorForm extends Model
{
    public ?int $id = null;
    public string $fio = '';

    public function rules(): array
    {
        return [
            [['fio'], 'required'],
            [['fio'], 'string', 'max' => 255],
            [['fio'], 'trim'],
            [
                ['fio'],
                'unique',
                'targetClass' => Author::class,
                'filter' => fn($query) => $this->id ? $query->andWhere(['<>', 'id', $this->id]) : $query,
                'message' => 'Автор с таким ФИО уже существует',
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'fio' => 'ФИО',
        ];
    }
}
