<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\queries;

use app\application\ports\SubscriptionQueryServiceInterface;
use app\application\ports\SubscriptionRepositoryInterface;
use app\domain\entities\Subscription;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\persistence\Subscription as SubscriptionAR;
use Codeception\Test\Unit;
use Yii;

final class SubscriptionQueryServiceTest extends Unit
{
    private const string PHONE_PRIMARY = '+77001234567';
    private const string PHONE_ALT1 = '+77001110000';
    private const string PHONE_ALT2 = '+77002220000';
    private const string PHONE_SHARED = '+77009999999';
    private const string TEST_AUTHOR = 'Test Author';

    protected \UnitTester $tester;
    private SubscriptionQueryServiceInterface $queryService;
    private SubscriptionRepositoryInterface $repository;

    protected function _before(): void
    {
        $this->queryService = Yii::$container->get(SubscriptionQueryServiceInterface::class);
        $this->repository = Yii::$container->get(SubscriptionRepositoryInterface::class);
        SubscriptionAR::deleteAll();
        Yii::$app->db->createCommand()->delete('book_authors')->execute();
        Book::deleteAll();
        Author::deleteAll();
    }

    public function testExistsReturnsTrueWhenSubscriptionExists(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => self::TEST_AUTHOR]);
        $subscription = Subscription::create(self::PHONE_PRIMARY, $authorId);
        $this->repository->save($subscription);

        $this->assertTrue($this->queryService->exists(self::PHONE_PRIMARY, $authorId));
    }

    public function testExistsReturnsFalseWhenSubscriptionMissing(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => self::TEST_AUTHOR]);

        $this->assertFalse($this->queryService->exists(self::PHONE_PRIMARY, $authorId));
    }

    public function testExistsReturnsFalseForDifferentAuthor(): void
    {
        $authorId1 = $this->tester->haveRecord(Author::class, ['fio' => 'Author One']);
        $authorId2 = $this->tester->haveRecord(Author::class, ['fio' => 'Author Two']);

        $subscription = Subscription::create(self::PHONE_PRIMARY, $authorId1);
        $this->repository->save($subscription);

        $this->assertFalse($this->queryService->exists(self::PHONE_PRIMARY, $authorId2));
    }

    public function testGetSubscriberPhonesForBookReturnsPhones(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'Book Author']);
        $bookId = $this->tester->haveRecord(Book::class, [
            'title' => 'Test Book',
            'year' => 2024,
            'isbn' => '9783161484100',
        ]);

        Yii::$app->db->createCommand()->insert('book_authors', [
            'book_id' => $bookId,
            'author_id' => $authorId,
        ])->execute();

        $subscription = Subscription::create(self::PHONE_PRIMARY, $authorId);
        $this->repository->save($subscription);

        $phones = iterator_to_array($this->queryService->getSubscriberPhonesForBook($bookId, 100));

        $this->assertCount(1, $phones);
        $this->assertSame(self::PHONE_PRIMARY, $phones[0]);
    }

    public function testGetSubscriberPhonesForBookReturnsEmptyForNoSubscriptions(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'Lonely Author']);
        $bookId = $this->tester->haveRecord(Book::class, [
            'title' => 'Lonely Book',
            'year' => 2024,
            'isbn' => '9783161484101',
        ]);

        Yii::$app->db->createCommand()->insert('book_authors', [
            'book_id' => $bookId,
            'author_id' => $authorId,
        ])->execute();

        $phones = iterator_to_array($this->queryService->getSubscriberPhonesForBook($bookId, 100));

        $this->assertEmpty($phones);
    }

    public function testGetSubscriberPhonesWorksAcrossMultipleAuthors(): void
    {
        $author1 = $this->tester->haveRecord(Author::class, ['fio' => 'Author One']);
        $author2 = $this->tester->haveRecord(Author::class, ['fio' => 'Author Two']);
        $bookId = $this->tester->haveRecord(Book::class, [
            'title' => 'Multi Author Book',
            'year' => 2024,
            'isbn' => '9783161484102',
        ]);

        Yii::$app->db->createCommand()->batchInsert('book_authors', ['book_id', 'author_id'], [
            [$bookId, $author1],
            [$bookId, $author2],
        ])->execute();

        $sub1 = Subscription::create(self::PHONE_ALT1, $author1);
        $sub2 = Subscription::create(self::PHONE_ALT2, $author2);
        $this->repository->save($sub1);
        $this->repository->save($sub2);

        $phones = iterator_to_array($this->queryService->getSubscriberPhonesForBook($bookId, 100));

        $this->assertCount(2, $phones);
        $this->assertContains(self::PHONE_ALT1, $phones);
        $this->assertContains(self::PHONE_ALT2, $phones);
    }

    public function testGetSubscriberPhonesReturnsDistinctPhones(): void
    {
        $author1 = $this->tester->haveRecord(Author::class, ['fio' => 'Author A']);
        $author2 = $this->tester->haveRecord(Author::class, ['fio' => 'Author B']);
        $bookId = $this->tester->haveRecord(Book::class, [
            'title' => 'Shared Subscriber Book',
            'year' => 2024,
            'isbn' => '9783161484103',
        ]);

        Yii::$app->db->createCommand()->batchInsert('book_authors', ['book_id', 'author_id'], [
            [$bookId, $author1],
            [$bookId, $author2],
        ])->execute();

        $sub1 = Subscription::create(self::PHONE_SHARED, $author1);
        $sub2 = Subscription::create(self::PHONE_SHARED, $author2);
        $this->repository->save($sub1);
        $this->repository->save($sub2);

        $phones = iterator_to_array($this->queryService->getSubscriberPhonesForBook($bookId, 100));

        $this->assertCount(1, $phones);
        $this->assertSame(self::PHONE_SHARED, $phones[0]);
    }
}
