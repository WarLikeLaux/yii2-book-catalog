<?php

declare(strict_types=1);

use app\application\books\commands\CreateBookCommand;
use app\application\books\usecases\CreateBookUseCase;
use app\application\common\exceptions\ApplicationException;
use app\application\common\pipeline\PipelineFactory;
use app\application\common\values\AuthorIdCollection;
use app\domain\values\BookStatus;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use yii\db\Query;
use yii\queue\db\Queue;

final class CreateBookUseCaseCest
{
    public function _before(IntegrationTester $_I): void
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
            authorIds: AuthorIdCollection::fromArray([$author1Id, $author2Id]),
            storedCover: null,
        );

        $useCase = Yii::$container->get(CreateBookUseCase::class);
        $pipelineFactory = Yii::$container->get(PipelineFactory::class);
        $bookId = $pipelineFactory->createDefault()->execute($command, $useCase);

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
            authorIds: AuthorIdCollection::fromArray([$authorId]),
            storedCover: null,
        );

        $useCase = Yii::$container->get(CreateBookUseCase::class);
        $pipelineFactory = Yii::$container->get(PipelineFactory::class);
        $bookId = $pipelineFactory->createDefault()->execute($command, $useCase);

        $queue = Yii::$app->get('queue');
        assert($queue instanceof Queue);

        $jobCount = (new Query())
            ->from('queue')
            ->where(['channel' => $queue->channel])
            ->count('*', Yii::$app->db);

        $I->assertEquals(0, $jobCount, 'No job should be published on book creation (draft)');

        $book = Book::findOne($bookId);
        $I->assertSame(BookStatus::Draft->value, $book->status, 'Book should be draft');
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
            authorIds: AuthorIdCollection::fromArray([$authorId]),
            storedCover: null,
        );

        $useCase = Yii::$container->get(CreateBookUseCase::class);
        $pipelineFactory = Yii::$container->get(PipelineFactory::class);

        $I->expectThrowable(ApplicationException::class, static function () use ($pipelineFactory, $useCase, $command): void {
            $pipelineFactory->createDefault()->execute($command, $useCase);
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
            authorIds: AuthorIdCollection::fromArray([99999]),
            storedCover: null,
        );

        $useCase = Yii::$container->get(CreateBookUseCase::class);
        $pipelineFactory = Yii::$container->get(PipelineFactory::class);

        try {
            $pipelineFactory->createDefault()->execute($command, $useCase);
            $I->fail('Expected exception was not thrown');
        } catch (Throwable $e) {
        }

        $finalCount = Book::find()->count();
        $I->assertEquals($initialCount, $finalCount, 'Transaction should rollback on error');
    }
}
