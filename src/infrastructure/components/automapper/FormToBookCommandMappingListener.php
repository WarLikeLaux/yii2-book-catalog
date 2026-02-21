<?php

declare(strict_types=1);

namespace app\infrastructure\components\automapper;

use app\application\books\commands\CreateBookCommand;
use app\application\books\commands\UpdateBookCommand;
use app\application\common\values\AuthorIdCollection;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;
use AutoMapper\Transformer\CallableTransformer;

final class FormToBookCommandMappingListener
{
    private const string AUTHOR_IDS = 'authorIds';
    private const array TARGETS = [
        CreateBookCommand::class,
        UpdateBookCommand::class,
    ];

    public function __invoke(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->source !== 'app\presentation\books\forms\BookForm') {
            return;
        }

        if (!in_array($event->mapperMetadata->target, self::TARGETS, true)) {
            return;
        }

        if (isset($event->properties[self::AUTHOR_IDS])) {
            return;
        }

        $transformer = new CallableTransformer(AuthorIdCollection::class . '::fromMixed');
        $propertyEvent = new PropertyMetadataEvent(
            mapperMetadata: $event->mapperMetadata,
            source: new SourcePropertyMetadata(self::AUTHOR_IDS),
            target: new TargetPropertyMetadata(self::AUTHOR_IDS),
            transformer: $transformer,
        );

        $event->properties[self::AUTHOR_IDS] = $propertyEvent;
    }
}
