<?php

declare(strict_types=1);

namespace tests\unit\presentation\books\mappers;

use app\application\books\commands\CreateBookCommand;
use app\application\books\commands\UpdateBookCommand;
use app\presentation\books\forms\BookForm;
use app\presentation\books\mappers\FormToBookCommandMappingListener;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Transformer\CallableTransformer;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class FormToBookCommandMappingListenerTest extends TestCase
{
    private FormToBookCommandMappingListener $listener;

    protected function setUp(): void
    {
        $this->listener = new FormToBookCommandMappingListener();
    }

    public function testInvokeAddsAuthorIdsTransformerForBookFormToCreateBookCommand(): void
    {
        $event = $this->createEventForMapping(BookForm::class, CreateBookCommand::class);

        ($this->listener)($event);

        $this->assertArrayHasKey('authorIds', $event->properties);
        $propertyEvent = $event->properties['authorIds'];
        $this->assertInstanceOf(CallableTransformer::class, $propertyEvent->transformer);
    }

    public function testInvokeAddsAuthorIdsTransformerForBookFormToUpdateBookCommand(): void
    {
        $event = $this->createEventForMapping(BookForm::class, UpdateBookCommand::class);

        ($this->listener)($event);

        $this->assertArrayHasKey('authorIds', $event->properties);
        $propertyEvent = $event->properties['authorIds'];
        $this->assertInstanceOf(CallableTransformer::class, $propertyEvent->transformer);
    }

    public function testInvokeDoesNothingForOtherSources(): void
    {
        $event = $this->createEventForMapping(\stdClass::class, CreateBookCommand::class);

        ($this->listener)($event);

        $this->assertCount(0, $event->properties);
    }

    public function testInvokeDoesNothingForOtherTargets(): void
    {
        $event = $this->createEventForMapping(BookForm::class, \stdClass::class);

        ($this->listener)($event);

        $this->assertCount(0, $event->properties);
    }

    public function testTransformAuthorIdsWithArrayConvertsToInts(): void
    {
        $collection = FormToBookCommandMappingListener::transformAuthorIds(['1', '2', '3']);

        $this->assertSame([1, 2, 3], $collection->toIntArray());
    }

    public function testTransformAuthorIdsWithNonArrayReturnsEmpty(): void
    {
        $collection = FormToBookCommandMappingListener::transformAuthorIds(null);

        $this->assertSame([], $collection->toIntArray());
    }

    public function testInvokeDoesNotOverrideExistingAuthorIds(): void
    {
        $event = $this->createEventForMapping(BookForm::class, CreateBookCommand::class);
        $existingProperty = $this->createStub(PropertyMetadataEvent::class);
        $event->properties['authorIds'] = $existingProperty;

        ($this->listener)($event);

        $this->assertSame($existingProperty, $event->properties['authorIds']);
    }

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    private function createEventForMapping(string $sourceClass, string $targetClass): GenerateMapperEvent
    {
        /** @var MapperMetadata&Stub $mapperMetadata */
        $mapperMetadata = $this->createStub(MapperMetadata::class);
        $mapperMetadata->source = $sourceClass;
        $mapperMetadata->target = $targetClass;

        return new GenerateMapperEvent($mapperMetadata);
    }
}
