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
        
        $this->assertNull($subscription->getId());
        $this->assertSame('79001112233', $subscription->getPhone());
        $this->assertSame(10, $subscription->getAuthorId());
        
        $subscription->setId(999);
        $this->assertSame(999, $subscription->getId());
    }

    public function testConstructor(): void
    {
        $subscription = new Subscription(1, '79008889900', 5);
        $this->assertSame(1, $subscription->getId());
        $this->assertSame('79008889900', $subscription->getPhone());
        $this->assertSame(5, $subscription->getAuthorId());
    }
}
