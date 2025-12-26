<?php

declare(strict_types=1);

use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\persistence\Subscription;
use app\infrastructure\persistence\User;

final class QueueJobIntegrationCest
{
    public function _before(\FunctionalTester $I): void
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testSubscriptionRepositoryGetPhones(\FunctionalTester $I): void
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

        $queryService = \Yii::$container->get(
            \app\application\subscriptions\queries\SubscriptionQueryService::class
        );

        $phones = iterator_to_array($queryService->getSubscriberPhonesForBook($bookId));

        $I->assertCount(2, $phones);
        $I->assertContains('+79001111111', $phones);
        $I->assertContains('+79002222222', $phones);
    }
}
