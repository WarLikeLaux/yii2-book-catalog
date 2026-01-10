<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\components\automapper;

use yii\db\ActiveRecord;

/**
 * @property string $duplicateProp
 * @property string $duplicateProp
 */
final class TestActiveRecordDuplicates extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'test_duplicates';
    }
}
