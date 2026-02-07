<?php

declare(strict_types=1);

use app\application\books\commands\UpdateBookCommand;
use app\application\books\usecases\UpdateBookUseCase;
use app\application\common\values\AuthorIdCollection;
use app\domain\exceptions\EntityNotFoundException;
use app\domain\exceptions\StaleDataException;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\persistence\User;

final class UpdateBookUseCaseCest
{
    public function _before(IntegrationTester $I): void
    {
        DbCleaner::clear(['book_authors', 'books', 'authors']);
        Yii::$container->clear(UpdateBookUseCase::class);
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testUpdatesBook(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Update Test Author']);
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Original Title',
            'year' => 2020,
            'isbn' => '9783161484100',
            'description' => 'Original description',
            'version' => 1,
        ]);
        Yii::$app->db->createCommand()
            ->insert('book_authors', ['book_id' => $bookId, 'author_id' => $authorId])
            ->execute();

        $command = new UpdateBookCommand(
            id: $bookId,
            title: 'Updated Title',
            year: 2024,
            isbn: '9783161484100',
            description: 'Updated description',
            authorIds: AuthorIdCollection::fromArray([$authorId]),
            version: 1,
            storedCover: null,
        );

        $useCase = Yii::$container->get(UpdateBookUseCase::class);
        $useCase->execute($command);

        $I->seeRecord(Book::class, [
            'id' => $bookId,
            'title' => 'Updated Title',
            'year' => 2024,
        ]);
    }

    public function testThrowsExceptionOnStaleData(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Stale Data Test Author']);
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Original Title',
            'year' => 2020,
            'isbn' => '9783161484101',
            'description' => 'Original description',
            'version' => 2,
        ]);
        Yii::$app->db->createCommand()
            ->insert('book_authors', ['book_id' => $bookId, 'author_id' => $authorId])
            ->execute();

        $command = new UpdateBookCommand(
            id: $bookId,
            title: 'Updated Title',
            year: 2024,
            isbn: '9783161484101',
            description: 'Updated description',
            authorIds: AuthorIdCollection::fromArray([$authorId]),
            version: 1,
            storedCover: null,
        );

        $useCase = Yii::$container->get(UpdateBookUseCase::class);
        $I->expectThrowable(StaleDataException::class, static function () use ($useCase, $command): void {
            $useCase->execute($command);
        });

        $I->seeRecord(Book::class, [
            'id' => $bookId,
            'title' => 'Original Title',
            'version' => 2,
        ]);
    }

    public function testThrowsExceptionWhenBookNotFound(IntegrationTester $I): void
    {
        $command = new UpdateBookCommand(
            id: 999,
            title: 'Updated Title',
            year: 2024,
            isbn: '9783161484102',
            description: 'Updated description',
            authorIds: AuthorIdCollection::fromArray([]),
            version: 1,
            storedCover: null,
        );

        $useCase = Yii::$container->get(UpdateBookUseCase::class);
        $I->expectThrowable(EntityNotFoundException::class, static function () use ($useCase, $command): void {
            $useCase->execute($command);
        });
    }
}
