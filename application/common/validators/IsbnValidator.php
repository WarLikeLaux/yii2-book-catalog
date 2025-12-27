<?php

declare(strict_types=1);

namespace app\application\common\validators;

use app\domain\values\Isbn;
use Exception;
use Yii;
use yii\validators\Validator;

/**
 * Validates ISBN-10 and ISBN-13 format by delegating to Domain Value Object.
 */
final class IsbnValidator extends Validator
{
    public $message;

    /**
     * @codeCoverageIgnore
     */
    public function init(): void
    {
        parent::init();
        if ($this->message !== null) {
            return;
        }

        $this->message = Yii::t('app', 'Invalid ISBN. Use ISBN-10 or ISBN-13 format.');
    }

    public function validateAttribute($model, $attribute): void
    {
        $value = $model->$attribute;

        if (!is_string($value)) {
            $this->addError($model, $attribute, (string)$this->message);
            return;
        }

        try {
            new Isbn($value);
        } catch (Exception $e) {
            $this->addError($model, $attribute, (string)$this->message);
        }
    }
}
