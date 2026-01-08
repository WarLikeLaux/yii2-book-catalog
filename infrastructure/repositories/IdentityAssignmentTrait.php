<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\domain\common\IdentifiableEntityInterface;
use ReflectionProperty;
use RuntimeException;

/**
 * NOTE: Используем рефлексию свойств для установки ID, чтобы сохранить инкапсуляцию домена.
 * @see docs/DECISIONS.md (см. пункт "4. Рефлексия для установки ID")
 */
trait IdentityAssignmentTrait
{
    /**
     * Assigns an identifier to an identifiable entity using reflection while preventing conflicting overwrites.
     *
     * If the entity already has a non-null ID different from the provided one, a RuntimeException is thrown.
     *
     * @param IdentifiableEntityInterface $entity The entity whose `id` property will be set.
     * @param int $id The identifier to assign to the entity.
     * @throws RuntimeException If the entity's existing non-null ID differs from `$id`.
     */
    private function assignId(IdentifiableEntityInterface $entity, int $id): void
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