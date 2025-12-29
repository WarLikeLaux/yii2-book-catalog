<?php

declare(strict_types=1);

namespace app\presentation\books\validators;

use app\domain\values\Isbn;
use Exception;
use Yii;
use yii\validators\Validator;

final class IsbnValidator extends Validator
{
    public $message;

    /**
     * @codeCoverageIgnore Yii2 framework инициализация валидатора
     */
    #[\Override]
    public function init(): void
    {
        parent::init();
        if ($this->message !== null) {
            return;
        }

        $this->message = Yii::t('app', 'Invalid ISBN. Use ISBN-10 or ISBN-13 format.');
    }

    #[\Override]
    public function validateAttribute($model, $attribute): void
    {
        $value = $model->$attribute;

        if (!is_string($value)) {
            $this->addError($model, $attribute, (string)$this->message);
            return;
        }

        try {
            new Isbn($value);
        } catch (Exception) {
            $this->addError($model, $attribute, (string)$this->message);
        }
    }
}
