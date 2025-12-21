<?php

declare(strict_types=1);

namespace app\application\books\usecases;

use app\application\books\commands\DeleteBookCommand;
use app\domain\exceptions\DomainException;
use app\models\Book;

final class DeleteBookUseCase
{
    public function execute(DeleteBookCommand $command): void
    {
        $book = Book::findOne($command->id);
        if (!$book) {
            throw new DomainException('Книга не найдена');
        }

        if (!$book->delete()) {
            throw new DomainException('Не удалось удалить книгу');
        }
    }
}
