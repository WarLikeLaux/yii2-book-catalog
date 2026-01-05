<?php

declare(strict_types=1);

namespace tests\unit\domain\entities;

use app\domain\entities\Subscription;
use Codeception\Test\Unit;

final class SubscriptionTest extends Unit
{
    public function testCreateAndGetters(): void
    {
        $subscription = Subscription::create('79001112233', 10);

        $this->assertNull($subscription->id);
        $this->assertSame('79001112233', $subscription->phone);
        $this->assertSame(10, $subscription->authorId);
    }

    public function testConstructor(): void
    {
        $subscription = new Subscription(5, '79008889900', 5);
        $this->assertSame(5, $subscription->id);
        $this->assertSame('79008889900', $subscription->phone);
        $this->assertSame(5, $subscription->authorId);
    }
}
