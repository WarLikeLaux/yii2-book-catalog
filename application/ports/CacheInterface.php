<?php

declare(strict_types=1);

namespace app\application\ports;

interface CacheInterface
{
    /**
     * Возвращает значение из кэша или вычисляет и сохраняет его.
     *
     * @template T
     * @param string $key ключ кэша
     * @param callable(): T $callback функция для вычисления значения
     * @param int $ttl время жизни в секундах
     * @return T
     */
    public function getOrSet(string $key, callable $callback, int $ttl = 3600): mixed;

    public function delete(string $key): void;

    public function deleteByPrefix(string $prefix): void;
}
