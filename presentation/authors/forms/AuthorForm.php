<?php

declare(strict_types=1);

namespace app\presentation\authors\forms;

use app\presentation\authors\validators\UniqueFioValidator;
use Yii;
use yii\base\Model;

final class AuthorForm extends Model
{
    /** @var int|string|null */
    public $id;

    /** @var string|int|null */
    public $fio = '';

    #[\Override]
    public function rules(): array
    {
        return [
            [['fio'], 'required'],
            [['fio'], 'string', 'max' => 255],
            [['fio'], 'trim'],
            [['fio'], UniqueFioValidator::class],
        ];
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return [
            'fio' => Yii::t('app', 'FIO'),
        ];
    }
}
