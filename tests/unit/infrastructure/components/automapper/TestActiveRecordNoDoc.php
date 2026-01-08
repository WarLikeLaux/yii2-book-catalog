<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\components\automapper;

use yii\db\ActiveRecord;

final class TestActiveRecordNoDoc extends ActiveRecord
{
    /**
     * Database table name for this ActiveRecord.
     *
     * @return string The table name associated with this ActiveRecord ('test_table_no_doc').
     */
    public static function tableName(): string
    {
        return 'test_table_no_doc';
    }
}