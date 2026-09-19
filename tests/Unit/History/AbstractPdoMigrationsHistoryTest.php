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

namespace FiveLab\Component\Migrator\Tests\Unit\History;

use FiveLab\Component\Migrator\History\AbstractPdoMigrationsHistory;
use FiveLab\Component\Migrator\MigrationExecutedState;
use FiveLab\Component\Migrator\MigrationMetadata;
use FiveLab\Component\Migrator\MigrationResult;
use FiveLab\Component\Migrator\Tests\Migrations\DataSet05\Gamma\Version1;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class AbstractPdoMigrationsHistoryTest extends TestCase
{
    #[Test]
    public function shouldFailOnErrorInSilentMode(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);

        $history = $this->createHistory($pdo);

        $metadata = new MigrationMetadata('default', '1', new \ReflectionClass(Version1::class));
        $result = new MigrationResult($metadata, MigrationExecutedState::Executed, new \DateTimeImmutable(), 0.1, null);

        $history->add($result);

        self::assertTrue($history->isExecuted($metadata));

        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage('UNIQUE constraint failed');

        $history->add($result);
    }

    private function createHistory(\PDO $pdo): AbstractPdoMigrationsHistory
    {
        return new class($pdo, 'migration_versions') extends AbstractPdoMigrationsHistory {
            protected function isMigrationTableExist(string $tableName): bool
            {
                $stmt = $this->pdo->prepare('SELECT 1 FROM sqlite_master WHERE type = \'table\' AND name = ?');
                $stmt->execute([$tableName]);

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
    `execution_time` DECIMAL(16, 6) NOT NULL,
    `description` VARCHAR(1000) DEFAULT NULL,
    PRIMARY KEY (`group`, `version`)
)
SQL;
            }
        };
    }
}
