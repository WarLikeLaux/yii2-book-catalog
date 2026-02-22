<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\components\automapper;

use yii\db\ActiveRecord;

final class TestActiveRecordNoDoc extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'test_table_no_doc';
    }
}
