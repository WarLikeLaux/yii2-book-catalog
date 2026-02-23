<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\components\automapper;

use app\infrastructure\components\automapper\Yii2ActiveRecordMappingListener;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Metadata\MapperMetadata;
use Codeception\Test\Unit;

final class Yii2ActiveRecordMappingListenerTest extends Unit
{
    private Yii2ActiveRecordMappingListener $listener;

    protected function _before(): void
    {
        $this->listener = new Yii2ActiveRecordMappingListener();
    }

    public function testSkipsNonActiveRecordSources(): void
    {
        $event = $this->createEventForSource(\stdClass::class);

        ($this->listener)($event);

        $this->assertCount(0, $event->properties);
    }

    public function testSkipsArraySource(): void
    {
        $event = $this->createEventForSource('array');

        ($this->listener)($event);

        $this->assertCount(0, $event->properties);
    }

    public function testExtractsPropertiesFromActiveRecordPhpDoc(): void
    {
        $event = $this->createEventForSource(TestActiveRecord::class);

        ($this->listener)($event);

        $this->assertArrayHasKey('id', $event->properties);
        $this->assertArrayHasKey('title', $event->properties);
        $this->assertArrayHasKey('description', $event->properties);
    }

    public function testCachesPropertyExtraction(): void
    {
        $event1 = $this->createEventForSource(TestActiveRecord::class);
        $event2 = $this->createEventForSource(TestActiveRecord::class);

        ($this->listener)($event1);
        ($this->listener)($event2);

        $this->assertArrayHasKey('title', $event1->properties);
        $this->assertArrayHasKey('title', $event2->properties);
    }

    public function testDoesNotOverrideExistingMappedProperties(): void
    {
        $event = $this->createEventForSource(TestActiveRecord::class);

        /** @var PropertyMetadataEvent&MockObject $existingProperty */
        $existingProperty = $this->createMock(PropertyMetadataEvent::class);
        $event->properties['title'] = $existingProperty;

        ($this->listener)($event);

        $this->assertSame($existingProperty, $event->properties['title']);
    }

    public function testHandlesActiveRecordWithoutPhpDoc(): void
    {
        $event = $this->createEventForSource(TestActiveRecordNoDoc::class);

        ($this->listener)($event);

        $this->assertCount(0, $event->properties);
    }

    public function testArrayUniqueRemovesDuplicateProperties(): void
    {
        $event = $this->createEventForSource(TestActiveRecordDuplicates::class);

        ($this->listener)($event);

        $propertyCount = count(array_filter(
            array_keys($event->properties),
            static fn($key) => $key === 'duplicateProp',
        ));

        $this->assertSame(1, $propertyCount);
    }

    /**
     * @param class-string $sourceClass
     */
    private function createEventForSource(string $sourceClass): GenerateMapperEvent
    {
        /** @var MapperMetadata&MockObject $mapperMetadata */
        $mapperMetadata = $this->createMock(MapperMetadata::class);
        $mapperMetadata->source = $sourceClass;
        $mapperMetadata->target = 'TestDto';

        return new GenerateMapperEvent($mapperMetadata);
    }
}
