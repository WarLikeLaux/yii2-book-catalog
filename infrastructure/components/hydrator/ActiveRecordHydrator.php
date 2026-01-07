<?php

declare(strict_types=1);

namespace app\infrastructure\components\hydrator;

use BackedEnum;
use Closure;
use yii\db\ActiveRecord;

final class ActiveRecordHydrator
{
    /**
     * @param array<int|string, string|Closure> $map
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

    private function extractValue(object $source, string $property): mixed
    {
        return $source->{$property};
    }

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

    private function extractPublicValueProperty(object $object): mixed
    {
        $publicProperties = get_object_vars($object);

        if (!array_key_exists('value', $publicProperties)) {
            return null;
        }

        return $publicProperties['value'];
    }
}
