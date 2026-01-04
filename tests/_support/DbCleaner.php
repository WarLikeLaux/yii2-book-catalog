<?php

declare(strict_types=1);

use yii\db\Connection;

final class DbCleaner
{
    /**
     * @param string[] $tables
     */
    public static function clear(array $tables): void
    {
        if ($tables === []) {
            return;
        }

        $db = Yii::$app->db;
        $transaction = $db->getTransaction();
        if ($transaction !== null && $transaction->getIsActive()) {
            $transaction->rollBack();
        }

        if ($db->driverName === 'pgsql') {
            self::truncatePostgres($db, $tables);
            return;
        }

        self::deleteWithDisabledForeignKeys($db, $tables);
    }

    /**
     * @param string[] $tables
     */
    private static function truncatePostgres(Connection $db, array $tables): void
    {
        $quotedTables = array_map(
            static fn (string $table): string => $db->quoteTableName($table),
            $tables
        );

        $db->createCommand(
            'TRUNCATE TABLE ' . implode(', ', $quotedTables) . ' RESTART IDENTITY CASCADE'
        )->execute();
    }

    /**
     * @param string[] $tables
     */
    private static function deleteWithDisabledForeignKeys(Connection $db, array $tables): void
    {
        $db->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();

        foreach ($tables as $table) {
            $db->createCommand()->delete($table)->execute();
        }

        $db->createCommand('SET FOREIGN_KEY_CHECKS=1')->execute();
    }
}
