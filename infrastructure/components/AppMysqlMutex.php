<?php

declare(strict_types=1);

namespace app\infrastructure\components;

use yii\mutex\MysqlMutex;

/**
 * NOTE: Обёртка для разрыва рекурсии DI.
 *
 * @see docs/DECISIONS.md (см. пункт "5. Автоматическое внедрение зависимостей")
 */
final class AppMysqlMutex extends MysqlMutex
{
}
