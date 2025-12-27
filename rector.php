<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/application',
        __DIR__ . '/domain',
        __DIR__ . '/infrastructure',
        __DIR__ . '/presentation',
    ]);

    // Определяем версию PHP
    $rectorConfig->phpVersion(PhpVersion::PHP_84);

    // Подключение наборов правил
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83, // Правила для миграции до версии PHP 8.3
        SetList::DEAD_CODE,         // Удаление неиспользуемого кода
        SetList::CODE_QUALITY,      // Улучшение качества кода
        SetList::PRIVATIZATION,     // Ограничение области видимости свойств и методов
        SetList::TYPE_DECLARATION,  // Добавление строгой типизации
    ]);

    // Исключения для специфичных файлов Yii2
    $rectorConfig->skip([
        __DIR__ . '/presentation/views/*',
        __DIR__ . '/infrastructure/persistence/*', // Исключаем Active Record модели во избежание конфликтов
    ]);
};
