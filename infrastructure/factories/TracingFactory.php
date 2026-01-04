<?php

declare(strict_types=1);

namespace app\infrastructure\factories;

use app\application\ports\TracerInterface;
use yii\di\Container;

final class TracingFactory
{
    /**
     * @template T of object
     * @param class-string<T>|T $service
     * @param class-string $decoratorClass
     */
    public static function create(Container $c, string|object $service, string $decoratorClass): object
    {
        if (is_string($service)) {
            $service = $c->get($service);
        }

        if ($c->has(TracerInterface::class)) {
            return new $decoratorClass($service, $c->get(TracerInterface::class));
        }

        return $service;
    }
}
