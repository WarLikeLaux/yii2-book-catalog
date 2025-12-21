<?php

declare(strict_types=1);

namespace app\validators;

use app\repositories\AuthorReadRepository;
use Yii;
use yii\validators\Validator;

final class UniqueFioValidator extends Validator
{
    public string|int|null $excludeId = null;

    public function __construct(
        private readonly AuthorReadRepository $repository,
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

        $repository = $this->repository;

        $query = $repository->findAllOrderedByFio()
            ->andWhere(['fio' => $value]);

        if ($excludeId !== null) {
            $query->andWhere(['<>', 'id', $excludeId]);
        }

        if (!$query->exists()) {
            return;
        }

        $this->addError(
            $model,
            $attribute,
            Yii::t('app', 'Author with this FIO already exists')
        );
    }
}
