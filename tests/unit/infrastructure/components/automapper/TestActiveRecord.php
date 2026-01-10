<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\components\automapper;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 */
final class TestActiveRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'test_table';
    }
}
