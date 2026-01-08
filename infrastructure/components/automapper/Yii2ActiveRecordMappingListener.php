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

/** @codeCoverageIgnore */
final class Yii2ActiveRecordMappingListener
{
    private const string PROPERTY_PATTERN = '/@property(?:-read|-write)?\s+([^\s]+)\s+\$([a-zA-Z_]\w*)/';

    /** @var array<class-string, string[]> */
    private array $cache = [];

    private function propertyAlreadyMapped(GenerateMapperEvent $event, string $propertyName): bool
    {
        return isset($event->properties[$propertyName]);
    }

    /** @param class-string $class */
    private function isActiveRecord(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        return is_subclass_of($class, ActiveRecord::class);
    }

    /**
     * @param class-string $class
     * @return string[]
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
