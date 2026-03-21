<?php

declare(strict_types=1);

namespace tests\unit\domain\entities;

use app\domain\entities\Subscription;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\ValidationException;
use app\domain\values\AuthorId;
use app\domain\values\Phone;
use PHPUnit\Framework\TestCase;

final class SubscriptionTest extends TestCase
{
    public function testCreateAndGetters(): void
    {
        $phone = new Phone('+79001112233');
        $authorId = new AuthorId(10);
        $subscription = Subscription::create($phone, $authorId);

        $this->assertNull($subscription->id);
        $this->assertNull($subscription->getId());
        $this->assertTrue($phone->equals($subscription->phone));
        $this->assertTrue($authorId->equals($subscription->authorId));
    }

    public function testReconstitute(): void
    {
        $phone = new Phone('+79008889900');
        $authorId = new AuthorId(5);
        $subscription = Subscription::reconstitute(5, $phone, $authorId);

        $this->assertSame(5, $subscription->id);
        $this->assertSame(5, $subscription->getId());
        $this->assertTrue($phone->equals($subscription->phone));
        $this->assertTrue($authorId->equals($subscription->authorId));
    }

    public function testCreateThrowsExceptionForZeroAuthorId(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::BookInvalidAuthorId->value);

        new AuthorId(0);
    }

    public function testCreateThrowsExceptionForNegativeAuthorId(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(DomainErrorCode::BookInvalidAuthorId->value);

        new AuthorId(-1);
    }
}
