<?php

declare(strict_types=1);

use app\application\books\commands\UpdateBookCommand;
use app\application\books\usecases\UpdateBookUseCase;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\persistence\User;

final class UpdateBookUseCaseCest
{
    public function _before(\IntegrationTester $I): void
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testUpdatesBook(\IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Update Test Author']);
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Original Title',
            'year' => 2020,
            'isbn' => '9783161484100',
            'description' => 'Original description',
        ]);
        \Yii::$app->db->createCommand()
            ->insert('book_authors', ['book_id' => $bookId, 'author_id' => $authorId])
            ->execute();

        $command = new UpdateBookCommand(
            id: $bookId,
            title: 'Updated Title',
            year: 2024,
            isbn: '9783161484100',
            description: 'Updated description',
            authorIds: [$authorId],
            cover: null
        );

        $useCase = \Yii::$container->get(UpdateBookUseCase::class);
        $useCase->execute($command);

        $I->seeRecord(Book::class, [
            'id' => $bookId,
            'title' => 'Updated Title',
            'year' => 2024,
        ]);
    }
}
