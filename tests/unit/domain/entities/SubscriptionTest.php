<?php

declare(strict_types=1);

namespace tests\unit\domain\entities;

use app\domain\entities\Subscription;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use app\domain\values\Phone;
use Codeception\Test\Unit;

final class SubscriptionTest extends Unit
{
    public function testCreateAndGetters(): void
    {
        $phone = new Phone('+79001112233');
        $subscription = Subscription::create($phone, 10);

        $this->assertNull($subscription->id);
        $this->assertNull($subscription->getId());
        $this->assertTrue($phone->equals($subscription->phone));
        $this->assertSame(10, $subscription->authorId);
    }

    public function testReconstitute(): void
    {
        $phone = new Phone('+79008889900');
        $subscription = Subscription::reconstitute(5, $phone, 5);

        $this->assertSame(5, $subscription->id);
        $this->assertSame(5, $subscription->getId());
        $this->assertTrue($phone->equals($subscription->phone));
        $this->assertSame(5, $subscription->authorId);
    }

    public function testCreateThrowsExceptionForZeroAuthorId(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::SubscriptionInvalidAuthorId->value);

        Subscription::create(new Phone('+79001112233'), 0);
    }

    public function testCreateThrowsExceptionForNegativeAuthorId(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::SubscriptionInvalidAuthorId->value);

        Subscription::create(new Phone('+79001112233'), -1);
    }
}
