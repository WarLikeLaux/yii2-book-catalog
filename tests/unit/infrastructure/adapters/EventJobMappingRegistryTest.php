<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\adapters;

use app\domain\events\BookPublishedEvent;
use app\domain\events\QueueableEvent;
use app\infrastructure\adapters\EventJobMappingRegistry;
use app\infrastructure\queue\NotifySubscribersJob;
use Codeception\Test\Unit;
use InvalidArgumentException;
use yii\queue\JobInterface;

final class EventJobMappingRegistryTest extends Unit
{
    public function testResolveWithClassStringInstantiatesJobViaReflection(): void
    {
        $registry = new EventJobMappingRegistry([
            BookPublishedEvent::class => NotifySubscribersJob::class,
        ]);
        $event = new BookPublishedEvent(42, 'Test Book', 2024);

        $job = $registry->resolve($event);

        $this->assertInstanceOf(NotifySubscribersJob::class, $job);
        $this->assertSame(42, $job->bookId);
        $this->assertSame('Test Book', $job->title);
    }

    public function testResolveWithCallableUsesFactory(): void
    {
        $registry = new EventJobMappingRegistry([
            BookPublishedEvent::class => static fn(BookPublishedEvent $e): NotifySubscribersJob => new NotifySubscribersJob(
                bookId: $e->bookId + 100,
                title: 'Custom: ' . $e->title,
            ),
        ]);
        $event = new BookPublishedEvent(42, 'Test Book', 2024);

        $job = $registry->resolve($event);

        $this->assertInstanceOf(NotifySubscribersJob::class, $job);
        $this->assertSame(142, $job->bookId);
        $this->assertSame('Custom: Test Book', $job->title);
    }

    public function testResolveThrowsExceptionForUnregisteredEvent(): void
    {
        $registry = new EventJobMappingRegistry([]);
        $event = new class implements QueueableEvent {
            public function getEventType(): string
            {
                return 'unknown.event';
            }

            /** @return array<string, mixed> */
            public function getPayload(): array
            {
                return [];
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No job mapping for event:');

        $registry->resolve($event);
    }

    public function testResolveThrowsExceptionForMissingRequiredParameter(): void
    {
        $jobClass = $this->createJobClassWithRequiredParam();
        $registry = new EventJobMappingRegistry([
            BookPublishedEvent::class => $jobClass,
        ]);
        $event = new BookPublishedEvent(42, 'Test Book', 2024);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required parameter 'missingParam'");

        $registry->resolve($event);
    }

    public function testResolveUsesDefaultValueForOptionalParameter(): void
    {
        $jobClass = $this->createJobClassWithOptionalParam();
        $registry = new EventJobMappingRegistry([
            BookPublishedEvent::class => $jobClass,
        ]);
        $event = new BookPublishedEvent(42, 'Test Book', 2024);

        $job = $registry->resolve($event);

        $this->assertInstanceOf(JobInterface::class, $job);
        $this->assertSame(42, $job->bookId);
        $this->assertSame('default', $job->optional);
    }

    public function testResolveWithJobWithoutConstructor(): void
    {
        $jobClass = $this->createJobClassWithoutConstructor();
        $registry = new EventJobMappingRegistry([
            BookPublishedEvent::class => $jobClass,
        ]);
        $event = new BookPublishedEvent(42, 'Test Book', 2024);

        $job = $registry->resolve($event);

        $this->assertInstanceOf(JobInterface::class, $job);
    }

    public function testHasReturnsTrueForRegisteredEvent(): void
    {
        $registry = new EventJobMappingRegistry([
            BookPublishedEvent::class => NotifySubscribersJob::class,
        ]);

        $this->assertTrue($registry->has(BookPublishedEvent::class));
    }

    public function testHasReturnsFalseForUnregisteredEvent(): void
    {
        $registry = new EventJobMappingRegistry([]);

        $this->assertFalse($registry->has(BookPublishedEvent::class));
    }

    /**
     * @return class-string<JobInterface>
     */
    private function createJobClassWithRequiredParam(): string
    {
        return get_class(new class (0, '') implements JobInterface {
            public function __construct(
                public int $bookId,
                public string $missingParam,
            ) {
            }

            public function execute(mixed $_queue): void
            {
            }
        });
    }

    /**
     * @return class-string<JobInterface>
     */
    private function createJobClassWithOptionalParam(): string
    {
        return get_class(new class (0) implements JobInterface {
            public function __construct(
                public int $bookId,
                public string $optional = 'default',
            ) {
            }

            public function execute(mixed $_queue): void
            {
            }
        });
    }

    /**
     * @return class-string<JobInterface>
     */
    private function createJobClassWithoutConstructor(): string
    {
        return get_class(new class implements JobInterface {
            public function execute(mixed $_queue): void
            {
            }
        });
    }
}
