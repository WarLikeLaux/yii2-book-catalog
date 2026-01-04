<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use ReflectionProperty;
use RuntimeException;

/**
 * NOTE: Используем рефлексию свойств для установки ID, чтобы сохранить инкапсуляцию домена.
 * @see docs/DECISIONS.md (см. пункт "4. Рефлексия для установки ID")
 */
trait IdentityAssignmentTrait
{
    private function assignId(object $entity, int $id): void
    {
        $property = new ReflectionProperty($entity::class, 'id');

        $currentValue = $property->getValue($entity);

        if ($currentValue !== null && $currentValue !== $id) {
            throw new RuntimeException(sprintf(
                'Cannot overwrite ID for %s (current: %s, new: %d)',
                $entity::class,
                is_scalar($currentValue) ? (string)$currentValue : gettype($currentValue),
                $id,
            ));
        }

        $property->setValue($entity, $id);
    }
}
