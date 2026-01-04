<?php

declare(strict_types=1);

use app\application\books\commands\CreateBookCommand;
use app\application\books\usecases\CreateBookUseCase;
use app\domain\exceptions\AlreadyExistsException;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use yii\db\Query;
use yii\queue\db\Queue;

final class CreateBookUseCaseCest
{
    public function _before(IntegrationTester $I): void
    {
        DbCleaner::clear(['book_authors', 'books', 'authors', 'queue']);
    }

    public function testCreatesBookWithAuthors(IntegrationTester $I): void
    {
        $author1Id = $I->haveRecord(Author::class, ['fio' => 'Test Author 1']);
        $author2Id = $I->haveRecord(Author::class, ['fio' => 'Test Author 2']);

        $command = new CreateBookCommand(
            title: 'Test Book',
            year: 2024,
            isbn: '9783161484100',
            description: 'Test description',
            authorIds: [$author1Id, $author2Id],
            cover: null
        );

        $useCase = Yii::$container->get(CreateBookUseCase::class);
        $bookId = $useCase->execute($command);

        $I->seeRecord(Book::class, [
            'id' => $bookId,
            'title' => 'Test Book',
            'isbn' => '9783161484100',
            'year' => 2024,
        ]);

        $book = Book::findOne($bookId);
        $I->assertCount(2, $book->authors);
        $I->assertContains($author1Id, array_column($book->authors, 'id'));
        $I->assertContains($author2Id, array_column($book->authors, 'id'));
    }

    public function testDoesNotPublishEventOnCreate(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Test Author']);

        $command = new CreateBookCommand(
            title: 'Draft Book',
            year: 2024,
            isbn: '9780306406157',
            description: 'Test',
            authorIds: [$authorId],
            cover: null
        );

        $useCase = Yii::$container->get(CreateBookUseCase::class);
        $bookId = $useCase->execute($command);

        $queue = Yii::$app->get('queue');
        assert($queue instanceof Queue);

        $jobCount = (new Query())
            ->from('queue')
            ->where(['channel' => $queue->channel])
            ->count('*', Yii::$app->db);

        $I->assertEquals(0, $jobCount, 'No job should be published on book creation (draft)');

        $book = Book::findOne($bookId);
        $I->assertEquals(0, $book->is_published, 'Book should be unpublished (draft)');
    }

    public function testValidatesUniqueIsbn(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Test Author']);
        $existingBookId = $I->haveRecord(Book::class, [
            'title' => 'Existing Book',
            'isbn' => '9783161484100',
            'year' => 2020,
            'description' => 'Existing',
        ]);

        $command = new CreateBookCommand(
            title: 'Duplicate ISBN Book',
            year: 2024,
            isbn: '9783161484100',
            description: 'Should fail',
            authorIds: [$authorId],
            cover: null
        );

        $useCase = Yii::$container->get(CreateBookUseCase::class);

        $I->expectThrowable(AlreadyExistsException::class, function () use ($useCase, $command): void {
            $useCase->execute($command);
        });
    }

    public function testRollbackOnError(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Test Author']);

        $initialCount = Book::find()->count();

        $command = new CreateBookCommand(
            title: 'Test Book',
            year: 2024,
            isbn: '9780306406157',
            description: 'Test',
            authorIds: [99999],
            cover: null
        );

        $useCase = Yii::$container->get(CreateBookUseCase::class);

        try {
            $useCase->execute($command);
            $I->fail('Expected exception was not thrown');
        } catch (Throwable $e) {
        }

        $finalCount = Book::find()->count();
        $I->assertEquals($initialCount, $finalCount, 'Transaction should rollback on error');
    }
}
