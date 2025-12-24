<?php

declare(strict_types=1);

use app\application\books\commands\CreateBookCommand;
use app\application\books\usecases\CreateBookUseCase;
use app\domain\exceptions\DomainException;
use app\infrastructure\queue\NotifySubscribersJob;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use yii\db\Query;
use yii\queue\db\Queue;

final class CreateBookUseCaseCest
{
    public function _before(\FunctionalTester $I): void
    {
        \Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
        \Yii::$app->db->createCommand()->delete('book_authors')->execute();
        \Yii::$app->db->createCommand()->delete('books')->execute();
        \Yii::$app->db->createCommand()->delete('queue')->execute();
        \Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS=1')->execute();
    }

    public function testCreatesBookWithAuthors(\FunctionalTester $I): void
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

        $useCase = \Yii::$container->get(CreateBookUseCase::class);
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

    public function testPublishesBookCreatedEvent(\FunctionalTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Test Author']);

        $command = new CreateBookCommand(
            title: 'Event Test Book',
            year: 2024,
            isbn: '9780306406157',
            description: 'Test',
            authorIds: [$authorId],
            cover: null
        );

        $useCase = \Yii::$container->get(CreateBookUseCase::class);
        $bookId = $useCase->execute($command);

        $queue = \Yii::$app->get('queue');
        assert($queue instanceof Queue);

        $jobCount = (new Query())
            ->from('queue')
            ->where(['channel' => $queue->channel])
            ->count('*', \Yii::$app->db);

        $I->assertGreaterThan(0, $jobCount, 'Job should be published to queue');

        $job = (new Query())
            ->from('queue')
            ->where(['channel' => $queue->channel])
            ->orderBy(['id' => SORT_DESC])
            ->limit(1)
            ->one(\Yii::$app->db);

        $I->assertNotNull($job, 'Job record should exist');
        $I->assertArrayHasKey('job', $job, 'Job should have job field');

        $jobData = unserialize($job['job'], ['allowed_classes' => [NotifySubscribersJob::class]]);

        if ($jobData === false) {
            $I->fail('Failed to unserialize job data');
        }

        $I->assertInstanceOf(NotifySubscribersJob::class, $jobData);
        assert($jobData instanceof NotifySubscribersJob);
        $I->assertEquals($bookId, $jobData->bookId);
        $I->assertEquals('Event Test Book', $jobData->title);
    }

    public function testValidatesUniqueIsbn(\FunctionalTester $I): void
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

        $useCase = \Yii::$container->get(CreateBookUseCase::class);

        $I->expectThrowable(\RuntimeException::class, function () use ($useCase, $command): void {
            $useCase->execute($command);
        });
    }

    public function testRollbackOnError(\FunctionalTester $I): void
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

        $useCase = \Yii::$container->get(CreateBookUseCase::class);

        try {
            $useCase->execute($command);
            $I->fail('Expected exception was not thrown');
        } catch (\Throwable $e) {
        }

        $finalCount = Book::find()->count();
        $I->assertEquals($initialCount, $finalCount, 'Transaction should rollback on error');
    }
}
