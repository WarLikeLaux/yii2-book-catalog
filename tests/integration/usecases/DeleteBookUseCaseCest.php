<?php

declare(strict_types=1);

use app\application\books\commands\DeleteBookCommand;
use app\application\books\usecases\DeleteBookUseCase;
use app\infrastructure\persistence\Book;
use app\infrastructure\persistence\User;

final class DeleteBookUseCaseCest
{
    public function _before(IntegrationTester $I): void
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testDeletesBook(IntegrationTester $I): void
    {
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Book To Delete Via UseCase',
            'year' => 2024,
            'isbn' => '9783161484100',
            'description' => 'Will be deleted',
        ]);

        $command = new DeleteBookCommand(id: $bookId);

        $useCase = Yii::$container->get(DeleteBookUseCase::class);
        $useCase->execute($command);

        $I->dontSeeRecord(Book::class, ['id' => $bookId]);
    }
}
