<?php

declare(strict_types=1);

namespace app\infrastructure\components\automapper;

use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;
use AutoMapper\Extractor\ReadAccessor;
use ReflectionClass;
use yii\db\ActiveRecord;

final class Yii2ActiveRecordMappingListener
{
    private const string PROPERTY_PATTERN = '/@property(?:-read|-write)?\s+([^\s]+)\s+\$([a-zA-Z_]\w*)/';

    /** @var array<class-string, string[]> */
    private array $cache = [];

    /**
     * Determines if a property name is already present in the event's properties.
     *
     * @param GenerateMapperEvent $event The mapper generation event containing current property metadata.
     * @param string $propertyName The property name to check for an existing mapping.
     * @return bool `true` if the property is already mapped in the event, `false` otherwise.
     */
    private function propertyAlreadyMapped(GenerateMapperEvent $event, string $propertyName): bool
    {
        return isset($event->properties[$propertyName]);
    }

    /**
     * Determine whether the given class is a Yii2 ActiveRecord subclass.
     *
     * @param class-string $class The fully-qualified class name to check.
     * @return bool `true` if the class exists and is a subclass of `yii\db\ActiveRecord`, `false` otherwise.
     */
    private function isActiveRecord(string $class): bool
    {
        if (!class_exists($class)) {
            return false; // @codeCoverageIgnore
        }

        return is_subclass_of($class, ActiveRecord::class);
    }

    /**
     * Extracts property names from a class's PHPDoc `@property` annotations.
     *
     * Results are cached per class to avoid repeated reflection.
     *
     * @param class-string $class Fully-qualified class name whose PHPDoc will be inspected.
     * @return string[] Property names declared via `@property`, `@property-read`, or `@property-write` in the class docblock.
     */
    private function extractPropertiesFromPhpDoc(string $class): array
    {
        if (isset($this->cache[$class])) {
            return $this->cache[$class];
        }

        $properties = [];

        $reflection = new ReflectionClass($class);
        $docComment = $reflection->getDocComment();

        if ($docComment === false) {
            $this->cache[$class] = $properties;
            return $properties;
        }

        if (preg_match_all(self::PROPERTY_PATTERN, $docComment, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $properties[] = $match[2];
            }
        }

        $properties = array_unique($properties);
        $this->cache[$class] = $properties;

        return $properties;
    }

    /**
     * Adds property mappings to the mapper generation event for properties declared via `@property` PHPDoc on Yii2 ActiveRecord sources.
     *
     * If the event's source is a class that extends `yii\db\ActiveRecord`, extracts property names from that class's PHPDoc and, for each property not already present in the event, creates a source property (with an array-style read accessor), a target property, and attaches a corresponding PropertyMetadataEvent to `$event->properties`.
     *
     * @param GenerateMapperEvent $event The mapper generation event to augment; modified in-place by adding PropertyMetadataEvent entries for discovered properties.
     */
    public function __invoke(GenerateMapperEvent $event): void
    {
        $sourceClass = $event->mapperMetadata->source;

        if ($sourceClass === 'array' || !$this->isActiveRecord($sourceClass)) {
            return;
        }

        $properties = $this->extractPropertiesFromPhpDoc($sourceClass);

        foreach ($properties as $propertyName) {
            if ($this->propertyAlreadyMapped($event, $propertyName)) {
                continue;
            }

            $accessor = new ReadAccessor(
                type: ReadAccessor::TYPE_ARRAY_ACCESS,
                accessor: $propertyName,
            );

            $sourceMetadata = new SourcePropertyMetadata($propertyName);
            $sourceMetadata->accessor = $accessor;

            $targetMetadata = new TargetPropertyMetadata($propertyName);

            $event->properties[$propertyName] = new PropertyMetadataEvent(
                $event->mapperMetadata,
                $sourceMetadata,
                $targetMetadata,
            );
        }
    }
}