<?php

declare(strict_types=1);

namespace app\infrastructure\components\automapper;

use app\application\books\queries\BookReadDto;
use app\infrastructure\persistence\Book;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;
use AutoMapper\Extractor\ReadAccessor;
use AutoMapper\Transformer\CallableTransformer;

final class BookToBookReadDtoMappingListener
{
    private const array PROPERTIES = [
        'authorIds' => 'getAuthorIds',
        'authorNames' => 'getAuthorNames',
        'coverUrl' => 'getCoverUrl',
    ];

    public function __invoke(GenerateMapperEvent $event): void
    {
        if (
            $event->mapperMetadata->source !== Book::class ||
            $event->mapperMetadata->target !== BookReadDto::class
        ) {
            return;
        }

        foreach (self::PROPERTIES as $propertyName => $accessorMethod) {
            if (isset($event->properties[$propertyName])) {
                continue;
            }

            $sourceMetadata = new SourcePropertyMetadata($propertyName);
            $sourceMetadata->accessor = new ReadAccessor(
                type: ReadAccessor::TYPE_METHOD,
                accessor: $accessorMethod,
                sourceClass: Book::class,
            );

            $propertyEvent = new PropertyMetadataEvent(
                $event->mapperMetadata,
                $sourceMetadata,
                new TargetPropertyMetadata($propertyName),
            );

            if ($propertyName === 'authorNames') {
                $propertyEvent->transformer = new CallableTransformer(
                    'mapAuthorNames',
                    callableIsMethodFromSource: true,
                );
            }

            $event->properties[$propertyName] = $propertyEvent;
        }
    }
}
