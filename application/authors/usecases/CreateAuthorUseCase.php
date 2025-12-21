<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\CreateAuthorCommand;
use app\domain\exceptions\DomainException;
use app\models\Author;
use Yii;

final class CreateAuthorUseCase
{
    public function execute(CreateAuthorCommand $command): Author
    {
        $author = Author::create($command->fio);

        if (!$author->save()) {
            throw new DomainException(Yii::t('app', 'Failed to create author'));
        }

        return $author;
    }
}
