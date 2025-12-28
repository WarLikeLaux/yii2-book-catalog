<?php

declare(strict_types=1);

namespace app\presentation\subscriptions\validators;

use app\application\authors\queries\AuthorReadDto;
use app\application\ports\AuthorRepositoryInterface;
use Yii;
use yii\validators\Validator;

final class AuthorExistsValidator extends Validator
{
    public function __construct(
        private readonly AuthorRepositoryInterface $repository,
        $config = []
    ) {
        parent::__construct($config);
    }

    #[\Override]
    public function validateAttribute($model, $attribute): void
    {
        $value = $model->$attribute;

        if (!is_int($value) && !is_string($value)) {
            $this->addError($model, $attribute, Yii::t('app', 'Invalid author ID'));
            return;
        }

        $authorId = (int)$value;

        if ($authorId <= 0) {
            $this->addError($model, $attribute, Yii::t('app', 'Invalid author ID'));
            return;
        }

        if ($this->repository->findById($authorId) instanceof AuthorReadDto) {
            return;
        }

        $this->addError($model, $attribute, Yii::t('app', 'Author does not exist'));
    }
}
