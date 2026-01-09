<?php

declare(strict_types=1);

namespace tests\_support;

use yii\db\ActiveRecord;

final class StubActiveRecord extends ActiveRecord
{
    public static ?self $next = null;
    public int|false $deleteResult = 1;

    public static function tableName(): string
    {
        return 'stub';
    }

    public static function findOne($condition): ?static
    {
        unset($condition);
        return self::$next;
    }

    public function delete(): int|false
    {
        return $this->deleteResult;
    }
}
