<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\UpdateAuthorCommand;
use app\domain\exceptions\DomainException;
use app\models\Author;
use Yii;

final class UpdateAuthorUseCase
{
    public function execute(UpdateAuthorCommand $command): Author
    {
        $author = Author::findOne($command->id);
        if (!$author) {
            throw new DomainException(Yii::t('app', 'Author not found'));
        }

        $author->edit($command->fio);

        if (!$author->save()) {
            throw new DomainException(Yii::t('app', 'Failed to update author'));
        }

        return $author;
    }
}
