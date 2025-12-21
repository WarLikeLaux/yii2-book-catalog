<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\DeleteBookCommand;
use app\domain\exceptions\DomainException;
use app\models\Book;
use Yii;

final class DeleteBookUseCase
{
    public function execute(DeleteBookCommand $command): void
    {
        $book = Book::findOne($command->id);
        if (!$book) {
            throw new DomainException(Yii::t('app', 'Book not found'));
        }

        if (!$book->delete()) {
            throw new DomainException(Yii::t('app', 'Failed to delete book'));
        }
    }
}
