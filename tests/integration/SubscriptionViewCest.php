<?php

declare(strict_types=1);

use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\User;
use app\presentation\subscriptions\handlers\SubscriptionViewDataFactory;

final class SubscriptionViewCest
{
    public function _before(IntegrationTester $I): void
    {
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testGetAuthor(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'View Service Author']);

        $factory = Yii::$container->get(SubscriptionViewDataFactory::class);
        $author = $factory->getAuthor($authorId);

        $I->assertSame('View Service Author', $author->fio);
    }
}
