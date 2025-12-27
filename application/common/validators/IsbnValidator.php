<?php

declare(strict_types=1);

namespace app\application\common\validators;

use app\application\ports\TranslatorInterface;
use app\domain\values\Isbn;
use Exception;
use yii\validators\Validator;

/**
 * Validates ISBN-10 and ISBN-13 format by delegating to Domain Value Object.
 */
final class IsbnValidator extends Validator
{
    public $message;

    public function __construct(
        private readonly TranslatorInterface $translator,
        array $config = []
    ) {
        parent::__construct($config);
    }

    #[\Override]
    public function init(): void
    {
        parent::init();
        if ($this->message !== null) {
            return;
        }

        $this->message = $this->translator->translate('app', 'Invalid ISBN. Use ISBN-10 or ISBN-13 format.');
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
