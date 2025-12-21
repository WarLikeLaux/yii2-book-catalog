<?php

declare(strict_types=1);

namespace app\validators;

use app\application\ports\BookRepositoryInterface;
use Yii;
use yii\validators\Validator;

final class UniqueIsbnValidator extends Validator
{
    public string|null $excludeId = null;

    public function __construct(
        private readonly BookRepositoryInterface $repository,
        $config = []
    ) {
        parent::__construct($config);
    }

    public function validateAttribute($model, $attribute): void
    {
        $value = $model->$attribute;

        if (!is_string($value)) {
            return;
        }

        $excludeId = $this->excludeId ?? ($model->id ?? null);
        if ($excludeId !== null && !is_int($excludeId)) {
            $excludeId = (int)$excludeId;
        }

        if (!$this->repository->existsByIsbn($value, $excludeId)) {
            return;
        }

        $this->addError(
            $model,
            $attribute,
            Yii::t('app', 'Book with this ISBN already exists')
        );
    }
}
