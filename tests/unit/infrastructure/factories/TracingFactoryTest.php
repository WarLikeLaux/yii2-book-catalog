<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\factories;

use app\application\ports\TracerInterface;
use app\infrastructure\factories\TracingFactory;
use Codeception\Test\Unit;
use yii\di\Container;

final class TracingFactoryTest extends Unit
{
    public function testCreateReturnsServiceWhenNoTracerAvailable(): void
    {
        $container = new Container();

        $service = new \stdClass();
        $container->set(\stdClass::class, $service);

        // Используем stdClass как фейковый декоратор, он не будет создан
        $result = TracingFactory::create($container, $service, \stdClass::class);

        $this->assertSame($service, $result);
    }

    public function testCreateReturnsDecoratorWhenTracerAvailable(): void
    {
        $container = new Container();
        $tracer = $this->createMock(TracerInterface::class);
        $container->setSingleton(TracerInterface::class, $tracer);

        $service = new \stdClass();

        // Создаем анонимный класс-декоратор, который имитирует ожидаемую структуру
        $decorator = new class ($service, $tracer) {
            public function __construct(
                public object $service,
                public TracerInterface $tracer
            ) {
            }
        };

        $result = TracingFactory::create($container, $service, $decorator::class);

        $this->assertInstanceOf($decorator::class, $result);
        $this->assertSame($service, $result->service);
    }

    public function testCreateResolvesStringServiceFromContainer(): void
    {
        $container = new Container();
        $service = new \stdClass();
        $container->set('testService', $service);

        // Используем stdClass как фейковый декоратор
        $result = TracingFactory::create($container, 'testService', \stdClass::class);

        $this->assertSame($service, $result);
    }
}
