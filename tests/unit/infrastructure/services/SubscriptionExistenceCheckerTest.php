<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services;

use app\application\ports\SubscriptionExistenceCheckerInterface;
use app\domain\entities\Subscription;
use app\domain\repositories\SubscriptionRepositoryInterface;
use app\domain\values\Phone;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Subscription as SubscriptionAR;
use Codeception\Test\Unit;
use Yii;

final class SubscriptionExistenceCheckerTest extends Unit
{
    protected \UnitTester $tester;
    private SubscriptionExistenceCheckerInterface $checker;
    private SubscriptionRepositoryInterface $repository;

    protected function _before(): void
    {
        $this->checker = Yii::$container->get(SubscriptionExistenceCheckerInterface::class);
        $this->repository = Yii::$container->get(SubscriptionRepositoryInterface::class);
        SubscriptionAR::deleteAll();
        Author::deleteAll();
    }

    public function testExistsReturnsTrueWhenSubscriptionExists(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'Test Author']);
        $subscription = Subscription::create(new Phone('+77001234567'), $authorId);
        $this->repository->save($subscription);

        $this->assertTrue($this->checker->exists('+77001234567', $authorId));
    }

    public function testExistsReturnsFalseWhenSubscriptionMissing(): void
    {
        $authorId = $this->tester->haveRecord(Author::class, ['fio' => 'Test Author']);

        $this->assertFalse($this->checker->exists('+77001234567', $authorId));
    }

    public function testExistsReturnsFalseForDifferentAuthor(): void
    {
        $authorId1 = $this->tester->haveRecord(Author::class, ['fio' => 'Author One']);
        $authorId2 = $this->tester->haveRecord(Author::class, ['fio' => 'Author Two']);
        $subscription = Subscription::create(new Phone('+77001234567'), $authorId1);
        $this->repository->save($subscription);

        $this->assertFalse($this->checker->exists('+77001234567', $authorId2));
    }
}
