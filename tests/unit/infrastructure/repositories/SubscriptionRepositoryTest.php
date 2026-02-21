<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\repositories;

use app\application\ports\SubscriptionRepositoryInterface;
use app\domain\entities\Subscription;
use app\domain\values\Phone;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Subscription as SubscriptionAR;
use Codeception\Test\Unit;
use Yii;

final class SubscriptionRepositoryTest extends Unit
{
    protected \UnitTester $tester;
    private SubscriptionRepositoryInterface $repository;

    protected function _before(): void
    {
        $this->repository = Yii::$container->get(SubscriptionRepositoryInterface::class);
        SubscriptionAR::deleteAll();
        Author::deleteAll();
    }

    public function testSaveCreatesSubscription(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'Test Author']);
        $subscription = Subscription::create(new Phone('+77001234567'), $authorId);

        $this->repository->save($subscription);

        $this->assertNotNull($subscription->id);
    }
}
