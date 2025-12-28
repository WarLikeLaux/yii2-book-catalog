<?php

declare(strict_types=1);

use app\application\subscriptions\queries\SubscriptionQueryService;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\persistence\Subscription;
use app\infrastructure\persistence\User;

final class QueueJobIntegrationCest
{
    public function _before(\IntegrationTester $I): void
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testSubscriptionRepositoryGetPhones(\IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Test Author']);
        $I->haveRecord(Subscription::class, [
            'phone' => '+79001111111',
            'author_id' => $authorId,
        ]);
        $I->haveRecord(Subscription::class, [
            'phone' => '+79002222222',
            'author_id' => $authorId,
        ]);

        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Test Book',
            'year' => 2024,
            'isbn' => '9783161484100',
            'description' => 'Test',
        ]);

        \Yii::$app->db->createCommand()
            ->insert('book_authors', ['book_id' => $bookId, 'author_id' => $authorId])
            ->execute();

        $queryService = \Yii::$container->get(SubscriptionQueryService::class);

        $phones = iterator_to_array($queryService->getSubscriberPhonesForBook($bookId));

        $I->assertCount(2, $phones);
        $I->assertContains('+79001111111', $phones);
        $I->assertContains('+79002222222', $phones);
    }
}
