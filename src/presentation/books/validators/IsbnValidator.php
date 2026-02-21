<?php

declare(strict_types=1);

namespace app\presentation\books\validators;

use app\application\common\services\IsbnFormatValidator;
use Override;
use Yii;
use yii\validators\Validator;

final class IsbnValidator extends Validator
{
    public $message;

    public function __construct(private readonly IsbnFormatValidator $formatValidator, array $config = [])
    {
        parent::__construct($config);
    }

    #[Override]
    public function init(): void
    {
        parent::init();

        if ($this->message !== null) {
            return;
        }

        $this->message = Yii::t('app', 'isbn.error.invalid_format_hint');
    }

    #[Override]
    public function validateAttribute($model, $attribute): void
    {
        $value = $model->$attribute;

        if (!is_string($value)) {
            $this->addError($model, $attribute, (string)$this->message);
            return;
        }

        if ($this->formatValidator->isValid($value)) {
            return;
        }

        $this->addError($model, $attribute, (string)$this->message);
    }
}
