<?php

declare(strict_types=1);

namespace app\presentation\books\validators;

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

        if (!is_array($value)) {
            return;
        }

        foreach ($value as $authorId) {
            if (!is_int($authorId) && !is_string($authorId)) {
                continue;
            }

            $authorId = is_string($authorId) ? (int)$authorId : $authorId;

            if ($this->repository->findById($authorId) instanceof AuthorReadDto) {
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
