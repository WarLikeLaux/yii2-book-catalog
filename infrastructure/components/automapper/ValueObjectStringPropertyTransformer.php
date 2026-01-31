<?php

declare(strict_types=1);

namespace app\infrastructure\components\automapper;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

final class ValueObjectStringPropertyTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface
{
    private string|null $currentTargetClass = null;

    public function supports(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        unset($types, $source);
        $targetClass = $this->getTargetClass($mapperMetadata, $target);

        if ($targetClass === null) {
            $this->currentTargetClass = null;
            return false;
        }

        if (!str_starts_with($targetClass, 'app\\domain\\values\\')) {
            $this->currentTargetClass = null;
            return false;
        }

        $isSupported = $this->isStringValueObject($targetClass);
        $this->currentTargetClass = $isSupported ? $targetClass : null;

        return $isSupported;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        unset($source, $context);
        $targetClass = $this->currentTargetClass;

        if ($targetClass !== null && $value instanceof $targetClass) {
            return $value;
        }

        if (is_string($value) && $targetClass !== null) {
            return new $targetClass($value);
        }

        return $value;
    }

    private function getTargetClass(MapperMetadata $mapperMetadata, TargetPropertyMetadata $target): string|null
    {
        $reflection = $mapperMetadata->targetReflectionClass;

        if (!$reflection instanceof ReflectionClass) {
            return null;
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return null;
        }

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->getName() !== $target->property) {
                continue;
            }

            $type = $parameter->getType();

            if ($type === null) {
                return null;
            }

            return $this->resolveClassName($type);
        }

        return null;
    }

    private function resolveClassName(ReflectionType $type): string|null
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->isBuiltin() ? null : $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            return $this->resolveUnionClassName($type);
        }

        return null;
    }

    private function resolveUnionClassName(ReflectionUnionType $type): string|null
    {
        $className = null;

        foreach ($type->getTypes() as $unionType) {
            if (!$unionType instanceof ReflectionNamedType || $unionType->isBuiltin()) {
                continue;
            }

            $name = $unionType->getName();

            if ($className !== null && $className !== $name) {
                return null;
            }

            $className = $name;
        }

        return $className;
    }

    private function isStringValueObject(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return false;
        }

        $parameters = $constructor->getParameters();

        if (count($parameters) !== 1) {
            return false;
        }

        $type = $parameters[0]->getType();

        if (!$type instanceof ReflectionNamedType) {
            return false;
        }

        return $type->getName() === 'string' && !$type->allowsNull();
    }
}
