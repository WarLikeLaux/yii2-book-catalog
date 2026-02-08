<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveParentDelegatingConstructorRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;
use Tools\Rector\AddCodeCoverageIgnoreToFormMethodsRector;
use Tools\Rector\MultilineViewVarAnnotationRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/application',
        __DIR__ . '/domain',
        __DIR__ . '/infrastructure',
        __DIR__ . '/presentation',
    ]);

    $rectorConfig->phpVersion(PhpVersion::PHP_84);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(true);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION,
    ]);

    $rectorConfig->rule(AddCodeCoverageIgnoreToFormMethodsRector::class);
    $rectorConfig->rule(MultilineViewVarAnnotationRector::class);

    $rectorConfig->skip([
        __DIR__ . '/infrastructure/persistence',
        __DIR__ . '/domain/entities',
        RemoveNonExistingVarAnnotationRector::class => [
            __DIR__ . '/presentation/views',
            __DIR__ . '/presentation/mail',
        ],
        RemoveParentDelegatingConstructorRector::class => [
            __DIR__ . '/domain/exceptions/AlreadyExistsException.php',
            __DIR__ . '/domain/exceptions/StaleDataException.php',
        ],
        RemoveUnusedPrivateMethodParameterRector::class => [
            __DIR__ . '/domain/values',
        ],
        RemoveUnusedPublicMethodParameterRector::class => [
            __DIR__ . '/domain/values',
        ],
    ]);
};
