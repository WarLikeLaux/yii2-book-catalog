<?php

declare(strict_types=1);

namespace tests\integration;

use Codeception\Test\Unit;
use ReflectionClass;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\di\Container;

final class ContainerIntegrityTest extends Unit
{
    /**
     * @throws ReflectionException
     */
    public function testContainerCanResolveAllRegisteredServices(): void
    {
        $container = Yii::$container;

        $reflection = new ReflectionClass($container);

        $definitionsProp = $reflection->getProperty('_definitions');
        $definitionsProp->setAccessible(true);

        /** @var mixed $definitionsRaw */
        $definitionsRaw = $definitionsProp->getValue($container);
        $definitions = is_array($definitionsRaw) ? $definitionsRaw : [];

        $singletonsProp = $reflection->getProperty('_singletons');
        $singletonsProp->setAccessible(true);

        /** @var mixed $singletonsRaw */
        $singletonsRaw = $singletonsProp->getValue($container);
        $singletons = is_array($singletonsRaw) ? $singletonsRaw : [];

        /** @var array<int, string> $services */
        $services = array_unique(array_merge(array_keys($definitions), array_keys($singletons)));

        $errors = [];

        foreach ($services as $service) {
            /** @var string|int $service */
            $error = $this->resolveServiceOrGetError($container, (string) $service);

            if ($error === null) {
                continue;
            }

            $errors[] = $error;
        }

        $this->assertEmpty($errors, "Failed to resolve some services:\n" . implode("\n", $errors));
    }

    private function resolveServiceOrGetError(Container $container, string $service): ?string
    {
        try {
            $container->get($service);
            return null;
        } catch (Throwable $e) {
            if (
                $e instanceof InvalidConfigException &&
                str_contains($e->getMessage(), 'must be configured to use') &&
                str_contains($e->getMessage(), 'database')
            ) {
                return null;
            }

            return sprintf('Service %s failed to load: %s', $service, $e->getMessage());
        }
    }
}
