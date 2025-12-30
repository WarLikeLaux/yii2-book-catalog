<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;
use Tools\Rector\AddCodeCoverageIgnoreToFormMethodsRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/application',
        __DIR__ . '/domain',
        __DIR__ . '/infrastructure',
        __DIR__ . '/presentation',
    ]);

    $rectorConfig->phpVersion(PhpVersion::PHP_84);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION,
    ]);

    $rectorConfig->rule(AddCodeCoverageIgnoreToFormMethodsRector::class);

    $rectorConfig->skip([
        __DIR__ . '/presentation/views/*',
        __DIR__ . '/infrastructure/persistence/*',
    ]);
};
