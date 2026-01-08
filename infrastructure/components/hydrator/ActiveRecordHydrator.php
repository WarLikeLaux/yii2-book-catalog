<?php

declare(strict_types=1);

namespace app\infrastructure\components\hydrator;

use BackedEnum;
use Closure;
use yii\db\ActiveRecord;

final class ActiveRecordHydrator
{
    /**
     * Populate an ActiveRecord with values taken from a source object according to a mapping.
     *
     * The `$map` describes how target fields are derived:
     * - If a map value is a `Closure`, it will be invoked with the `$source` and its return value assigned to the target field.
     * - If a map key is an integer, the map value is treated as the target field name (numeric-key shorthand).
     * - Otherwise the map value is treated as a property name on `$source`; that property's value is assigned to the target field after "unboxing" (e.g., extracting `BackedEnum::value` or a public `value` property on objects).
     *
     * @param array<int|string, string|Closure> $map Mapping of target field => source property name or Closure (or numeric-key shorthand).
     */
    public function hydrate(ActiveRecord $target, object $source, array $map): void
    {
        foreach ($map as $targetField => $sourceField) {
            if ($sourceField instanceof Closure) {
                $target->{$targetField} = $sourceField($source);
                continue;
            }

            if (is_int($targetField)) {
                $targetField = $sourceField;
            }

            $value = $this->extractValue($source, $sourceField);
            $target->{$targetField} = $this->unbox($value);
        }
    }

    /**
     * Retrieve a property value from the source object by name.
     *
     * @param object $source The object to read the property from.
     * @param string $property The property name to extract.
     * @return mixed The value of the specified property from the source object.
     */
    private function extractValue(object $source, string $property): mixed
    {
        return $source->{$property};
    }

    /**
     * Normalize a value by extracting underlying scalar or a public `value` property when present.
     *
     * @param mixed $value The value to normalize or unwrap.
     * @return mixed `null` if input is null; for `BackedEnum` returns its backing value; for non-objects returns the original value; for objects returns the public `value` property if present, otherwise the original object.
     */
    private function unbox(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (!is_object($value)) {
            return $value;
        }

        return $this->extractPublicValueProperty($value) ?? $value;
    }

    /**
     * Retrieve the public `value` property from an object, if present.
     *
     * @param object $object The object to inspect for a public `value` property.
     * @return mixed The `value` property's value if it exists and is public, `null` otherwise.
     */
    private function extractPublicValueProperty(object $object): mixed
    {
        $publicProperties = get_object_vars($object);

        if (!array_key_exists('value', $publicProperties)) {
            return null;
        }

        return $publicProperties['value'];
    }
}