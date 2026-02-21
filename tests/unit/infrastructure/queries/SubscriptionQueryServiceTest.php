<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\queries;

use app\application\ports\SubscriptionQueryServiceInterface;
use app\application\ports\SubscriptionRepositoryInterface;
use app\domain\entities\Subscription;
use app\domain\values\Phone;
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
        $authorId = $this->createAuthor(self::TEST_AUTHOR);
        $this->createSubscription(self::PHONE_PRIMARY, $authorId);

        $this->assertTrue($this->queryService->exists(self::PHONE_PRIMARY, $authorId));
    }

    public function testExistsReturnsFalseWhenSubscriptionMissing(): void
    {
        $authorId = $this->createAuthor(self::TEST_AUTHOR);

        $this->assertFalse($this->queryService->exists(self::PHONE_PRIMARY, $authorId));
    }

    public function testExistsReturnsFalseForDifferentAuthor(): void
    {
        $authorId1 = $this->createAuthor('Author One');
        $authorId2 = $this->createAuthor('Author Two');

        $this->createSubscription(self::PHONE_PRIMARY, $authorId1);

        $this->assertFalse($this->queryService->exists(self::PHONE_PRIMARY, $authorId2));
    }

    public function testGetSubscriberPhonesForBookReturnsPhones(): void
    {
        $authorId = $this->createAuthor('Book Author');
        $bookId = $this->createBook('Test Book', '9783161484100');
        $this->linkBookAuthor($bookId, $authorId);
        $this->createSubscription(self::PHONE_PRIMARY, $authorId);

        $phones = $this->getSubscriberPhones($bookId);

        $this->assertCount(1, $phones);
        $this->assertSame(self::PHONE_PRIMARY, $phones[0]);
    }

    public function testGetSubscriberPhonesForBookReturnsEmptyForNoSubscriptions(): void
    {
        $authorId = $this->createAuthor('Lonely Author');
        $bookId = $this->createBook('Lonely Book', '9783161484101');
        $this->linkBookAuthor($bookId, $authorId);

        $phones = $this->getSubscriberPhones($bookId);

        $this->assertEmpty($phones);
    }

    public function testGetSubscriberPhonesWorksAcrossMultipleAuthors(): void
    {
        $author1 = $this->createAuthor('Author One');
        $author2 = $this->createAuthor('Author Two');
        $bookId = $this->createBook('Multi Author Book', '9783161484102');
        $this->linkBookAuthors($bookId, [$author1, $author2]);
        $this->createSubscription(self::PHONE_ALT1, $author1);
        $this->createSubscription(self::PHONE_ALT2, $author2);

        $phones = $this->getSubscriberPhones($bookId);

        $this->assertCount(2, $phones);
        $this->assertContains(self::PHONE_ALT1, $phones);
        $this->assertContains(self::PHONE_ALT2, $phones);
    }

    public function testGetSubscriberPhonesReturnsDistinctPhones(): void
    {
        $author1 = $this->createAuthor('Author A');
        $author2 = $this->createAuthor('Author B');
        $bookId = $this->createBook('Shared Subscriber Book', '9783161484103');
        $this->linkBookAuthors($bookId, [$author1, $author2]);
        $this->createSubscription(self::PHONE_SHARED, $author1);
        $this->createSubscription(self::PHONE_SHARED, $author2);

        $phones = $this->getSubscriberPhones($bookId);

        $this->assertCount(1, $phones);
        $this->assertSame(self::PHONE_SHARED, $phones[0]);
    }

    private function createAuthor(string $fio): int
    {
        return $this->tester->haveRecord(Author::class, ['fio' => $fio]);
    }

    private function createBook(string $title, string $isbn): int
    {
        return $this->tester->haveRecord(Book::class, [
            'title' => $title,
            'year' => 2024,
            'isbn' => $isbn,
        ]);
    }

    private function linkBookAuthor(int $bookId, int $authorId): void
    {
        Yii::$app->db->createCommand()->insert('book_authors', [
            'book_id' => $bookId,
            'author_id' => $authorId,
        ])->execute();
    }

    /**
     * @param int[] $authorIds
     */
    private function linkBookAuthors(int $bookId, array $authorIds): void
    {
        $rows = array_map(static fn(int $authorId): array => [$bookId, $authorId], $authorIds);

        Yii::$app->db->createCommand()->batchInsert('book_authors', ['book_id', 'author_id'], $rows)
            ->execute();
    }

    private function createSubscription(string $phone, int $authorId): void
    {
        $subscription = Subscription::create(new Phone($phone), $authorId);
        $this->repository->save($subscription);
    }

    /**
     * @return string[]
     */
    private function getSubscriberPhones(int $bookId): array
    {
        return iterator_to_array($this->queryService->getSubscriberPhonesForBook($bookId, 100));
    }
}
