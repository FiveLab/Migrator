<?php

/*
 * This file is part of the FiveLab Migrator package
 *
 * (c) FiveLab
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

declare(strict_types = 1);

namespace FiveLab\Component\Migrator\History;

class MySqlPdoMigrationsHistory extends AbstractPdoMigrationsHistory
{
    protected function isMigrationTableExist(string $tableName): bool
    {
        $sql = \sprintf('SHOW TABLES LIKE "%s"', $tableName);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    protected function getCreateTableSql(string $tableName): string
    {
        return <<<SQL
CREATE TABLE `{$tableName}` (
    `group` VARCHAR(255) NOT NULL,
    `version` VARCHAR(255) NOT NULL,
    `fqcn` VARCHAR(500) NOT NULL,
    `executed_at` DATETIME NOT NULL,
    `execute_time` DECIMAL(16, 6) NOT NULL,
    `description` VARCHAR(1000) DEFAULT NULL,
    PRIMARY KEY (`group`, `version`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL;
    }
}
