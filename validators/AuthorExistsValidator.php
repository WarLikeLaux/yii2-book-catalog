<?php

declare(strict_types=1);

namespace app\validators;

use app\repositories\AuthorReadRepository;
use Yii;
use yii\validators\Validator;

final class AuthorExistsValidator extends Validator
{
    public function __construct(
        private readonly AuthorReadRepository $repository,
        $config = []
    ) {
        parent::__construct($config);
    }

    public function validateAttribute($model, $attribute): void
    {
        $value = $model->$attribute;

        if (!is_array($value)) {
            return;
        }

        $repository = $this->repository;

        foreach ($value as $authorId) {
            if (!is_int($authorId) && !is_string($authorId)) {
                continue;
            }

            $authorId = is_string($authorId) ? (int)$authorId : $authorId;

            $author = $repository->findById($authorId);
            if ($author !== null) {
                continue;
            }

            $this->addError(
                $model,
                $attribute,
                Yii::t('app', 'Author with ID {id} does not exist', ['id' => $authorId])
            );
        }
    }
}
