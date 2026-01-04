<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters\decorators;

use app\application\ports\QueueInterface;
use app\application\ports\TracerInterface;
use app\infrastructure\adapters\decorators\QueueTracingDecorator;
use Codeception\Test\Unit;

final class QueueTracingDecoratorTest extends Unit
{
    public function testPushDelegatesToInnerQueueWithTracing(): void
    {
        $innerQueue = $this->createMock(QueueInterface::class);
        $tracer = $this->createMock(TracerInterface::class);
        $decorator = new QueueTracingDecorator($innerQueue, $tracer);
        $job = new \stdClass();

        $tracer->expects($this->once())
            ->method('trace')
            ->with(
                'Queue::push',
                $this->isType('callable'),
                ['job_class' => \stdClass::class],
            )
            ->willReturnCallback(static fn(string $_name, callable $callback) => $callback());

        $innerQueue->expects($this->once())
            ->method('push')
            ->with($job);

        $decorator->push($job);
    }
}
