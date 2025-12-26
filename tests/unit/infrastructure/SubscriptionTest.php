<?php

declare(strict_types=1);

namespace tests\unit\infrastructure;

use app\infrastructure\persistence\Subscription;
use Codeception\Test\Unit;

final class SubscriptionTest extends Unit
{
    public function testCreate(): void
    {
        $subscription = Subscription::create('+79001234567', 1);
        
        $this->assertSame('+79001234567', $subscription->phone);
        $this->assertSame(1, $subscription->author_id);
    }

    public function testTableName(): void
    {
        $this->assertSame('subscriptions', Subscription::tableName());
    }

    public function testBehaviors(): void
    {
        $subscription = new Subscription();
        $behaviors = $subscription->behaviors();
        
        $this->assertIsArray($behaviors);
        $this->assertNotEmpty($behaviors);
    }

    public function testRules(): void
    {
        $subscription = new Subscription();
        $rules = $subscription->rules();
        
        $this->assertIsArray($rules);
        $this->assertNotEmpty($rules);
    }

    public function testAttributeLabels(): void
    {
        $subscription = new Subscription();
        $labels = $subscription->attributeLabels();
        
        $this->assertArrayHasKey('phone', $labels);
        $this->assertArrayHasKey('author_id', $labels);
    }
}
