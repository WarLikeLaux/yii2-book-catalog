<?php

declare(strict_types=1);

function env(string $key, mixed $default = null): mixed
{
    $envValue = getenv($key);
    $value = $envValue !== false ? $envValue : ($_ENV[$key] ?? $_SERVER[$key] ?? null);
    if ($value === null || $value === '') {
        return $default;
    }
    if (!is_string($value)) {
        return $value;
    }
    $lower = strtolower($value);
    if ($lower === 'true') {
        return true;
    }
    if ($lower === 'false') {
        return false;
    }
    return $value;
}
