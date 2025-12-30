<?php

declare(strict_types=1);

namespace app\presentation\common\forms;

use Yii;
use yii\base\Model;

abstract class RepositoryAwareForm extends Model
{
    /**
     * @template T of object
     * @param class-string<T> $interface
     * @return T
     */
    protected function resolve(string $interface): object
    {
        /** @var T */
        return Yii::$container->get($interface);
    }
}
