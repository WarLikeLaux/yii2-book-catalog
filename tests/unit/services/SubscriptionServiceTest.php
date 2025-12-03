<?php

declare(strict_types=1);

namespace tests\unit\services;

use app\models\forms\SubscriptionForm;
use app\models\Subscription;
use app\services\SubscriptionService;
use Codeception\Test\Unit;
use DomainException;
use yii\db\Connection;

final class SubscriptionServiceTest extends Unit
{
    private SubscriptionService $service;
    private Connection $db;

    protected function _before(): void
    {
        parent::_before();

        $this->db = \Yii::$app->db;
        $this->service = new SubscriptionService();

        $this->db->createCommand()->delete('subscriptions')->execute();
        $this->db->createCommand()->delete('authors')->execute();
    }

    public function testSubscribeNormalizesPhoneBeforeSaving(): void
    {
        $authorId = $this->createAuthor('Author One');

        $form = new SubscriptionForm();
        $form->phone = '+7 (900) 123-45-67';
        $form->authorId = $authorId;

        verify($form->validate())->true();

        $subscription = $this->service->subscribe($form);

        verify($subscription->phone)->equals('+79001234567');

        $fromDb = Subscription::findOne($subscription->id);
        verify($fromDb)->notNull();
        verify($fromDb->phone)->equals('+79001234567');
    }

    public function testSubscribeThrowsOnDuplicateAfterNormalization(): void
    {
        $authorId = $this->createAuthor('Author One');

        $firstForm = new SubscriptionForm();
        $firstForm->phone = '+7 900 123 45 67';
        $firstForm->authorId = $authorId;
        verify($firstForm->validate())->true();
        $this->service->subscribe($firstForm);

        $secondForm = new SubscriptionForm();
        $secondForm->phone = '+7-900-123-45-67';
        $secondForm->authorId = $authorId;
        verify($secondForm->validate())->true();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Вы уже подписаны на этого автора');

        $this->service->subscribe($secondForm);
    }

    private function createAuthor(string $name): int
    {
        $this->db->createCommand()->insert('authors', [
            'fio' => $name,
        ])->execute();

        return (int)$this->db->getLastInsertID();
    }
}
