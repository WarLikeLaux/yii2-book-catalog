<?php

declare(strict_types=1);

namespace app\application\authors\usecases;

use app\application\authors\commands\DeleteAuthorCommand;
use app\domain\exceptions\DomainException;
use app\models\Author;
use Yii;

final class DeleteAuthorUseCase
{
    public function execute(DeleteAuthorCommand $command): void
    {
        $author = Author::findOne($command->id);
        if (!$author) {
            throw new DomainException(Yii::t('app', 'Author not found'));
        }

        if (!$author->delete()) {
            throw new DomainException(Yii::t('app', 'Failed to delete author'));
        }
    }
}
