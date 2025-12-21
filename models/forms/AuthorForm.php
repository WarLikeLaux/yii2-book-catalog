<?php

declare(strict_types=1);

namespace app\models\forms;

use app\models\Author;
use Yii;
use yii\base\Model;

final class AuthorForm extends Model
{
    /** @var int|string|null */
    public $id = null;

    /** @var string */
    public $fio = '';

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
                'message' => Yii::t('app', 'Author with this FIO already exists'),
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'fio' => Yii::t('app', 'FIO'),
        ];
    }
}
