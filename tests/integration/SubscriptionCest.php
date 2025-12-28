<?php

declare(strict_types=1);

use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Subscription;

final class SubscriptionCest
{
    public function testCanSubscribeToAuthor(\IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Author for Subscription']);

        $I->sendAjaxPostRequest('/index-test.php?r=subscription/subscribe', [
            'SubscriptionForm[phone]' => '+79001234567',
            'SubscriptionForm[authorId]' => $authorId,
        ]);

        $I->seeResponseCodeIs(200);
        $response = $I->grabPageSource();
        $I->assertStringContainsString('"success":true', $response);

        $I->seeRecord(Subscription::class, [
            'phone' => '+79001234567',
            'author_id' => $authorId,
        ]);
    }

}

