<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\components\automapper;

use app\application\books\queries\BookReadDto;
use app\infrastructure\components\automapper\BookToBookReadDtoMappingListener;
use app\infrastructure\persistence\Book;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Transformer\CallableTransformer;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class BookToBookReadDtoMappingListenerTest extends Unit
{
    private BookToBookReadDtoMappingListener $listener;

    protected function _before(): void
    {
        $this->listener = new BookToBookReadDtoMappingListener();
    }

    public function testInvokeAddsPropertiesForBookToBookReadDtoMapping(): void
    {
        $event = $this->createEventForMapping(Book::class, BookReadDto::class);

        ($this->listener)($event);

        $this->assertArrayHasKey('authorIds', $event->properties);
        $this->assertArrayHasKey('authorNames', $event->properties);
        $this->assertArrayHasKey('coverUrl', $event->properties);
    }

    public function testInvokeDoesNothingForOtherMappings(): void
    {
        $event = $this->createEventForMapping(\stdClass::class, BookReadDto::class);

        ($this->listener)($event);

        $this->assertCount(0, $event->properties);
    }

    public function testInvokeDoesNothingWhenTargetIsNotBookReadDto(): void
    {
        $event = $this->createEventForMapping(Book::class, \stdClass::class);

        ($this->listener)($event);

        $this->assertCount(0, $event->properties);
    }

    public function testAuthorNamesPropertyHasCallableTransformer(): void
    {
        $event = $this->createEventForMapping(Book::class, BookReadDto::class);

        ($this->listener)($event);

        $propertyEvent = $event->properties['authorNames'];
        $this->assertNotNull($propertyEvent->transformer);
        $this->assertInstanceOf(CallableTransformer::class, $propertyEvent->transformer);
    }

    public function testAuthorIdsPropertyHasNoTransformer(): void
    {
        $event = $this->createEventForMapping(Book::class, BookReadDto::class);

        ($this->listener)($event);

        $propertyEvent = $event->properties['authorIds'];
        $this->assertNull($propertyEvent->transformer);
    }

    public function testCoverUrlPropertyHasNoTransformer(): void
    {
        $event = $this->createEventForMapping(Book::class, BookReadDto::class);

        ($this->listener)($event);

        $propertyEvent = $event->properties['coverUrl'];
        $this->assertNull($propertyEvent->transformer);
    }

    public function testDoesNotOverrideExistingProperties(): void
    {
        $event = $this->createEventForMapping(Book::class, BookReadDto::class);

        $existingProperty = $this->createMock(PropertyMetadataEvent::class);
        $event->properties['authorIds'] = $existingProperty;

        ($this->listener)($event);

        $this->assertSame($existingProperty, $event->properties['authorIds']);
        $this->assertCount(3, $event->properties);
    }

    public function testDoesNotOverrideExistingAuthorNamesProperty(): void
    {
        $event = $this->createEventForMapping(Book::class, BookReadDto::class);

        $existingProperty = $this->createMock(PropertyMetadataEvent::class);
        $event->properties['authorNames'] = $existingProperty;

        ($this->listener)($event);

        $this->assertSame($existingProperty, $event->properties['authorNames']);
        $this->assertCount(3, $event->properties);
    }

    public function testDoesNotOverrideExistingCoverUrlProperty(): void
    {
        $event = $this->createEventForMapping(Book::class, BookReadDto::class);

        $existingProperty = $this->createMock(PropertyMetadataEvent::class);
        $event->properties['coverUrl'] = $existingProperty;

        ($this->listener)($event);

        $this->assertSame($existingProperty, $event->properties['coverUrl']);
        $this->assertCount(3, $event->properties);
    }

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    private function createEventForMapping(string $sourceClass, string $targetClass): GenerateMapperEvent
    {
        /** @var MapperMetadata&MockObject $mapperMetadata */
        $mapperMetadata = $this->createMock(MapperMetadata::class);
        $mapperMetadata->source = $sourceClass;
        $mapperMetadata->target = $targetClass;

        return new GenerateMapperEvent($mapperMetadata);
    }
}
