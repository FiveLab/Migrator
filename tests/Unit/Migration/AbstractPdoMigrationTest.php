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

namespace FiveLab\Component\Migrator\Tests\Unit\Migration;

use FiveLab\Component\Migrator\Exception\MigrationFailedException;
use FiveLab\Component\Migrator\Migration\AbstractPdoMigration;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class AbstractPdoMigrationTest extends TestCase
{
    #[Test]
    #[TestWith([\PDO::ERRMODE_SILENT])]
    #[TestWith([\PDO::ERRMODE_EXCEPTION])]
    public function shouldFailOnExecuteError(int $errorMode): void
    {
        $pdo = $this->createPdo($errorMode);

        $migration = $this->createMigration($pdo, [
            'CREATE TABLE users (id INTEGER PRIMARY KEY)',
            'INSERT INTO users (id) VALUES (1)',
            'INSERT INTO users (id) VALUES (1)',
        ]);

        try {
            $migration->up();
        } catch (MigrationFailedException $error) {
            self::assertStringContainsString('"INSERT INTO users (id) VALUES (1)"', $error->getMessage());
            self::assertStringContainsString('UNIQUE constraint failed', $error->getMessage());
            self::assertInstanceOf(\PDOException::class, $error->getPrevious());

            return;
        }

        self::fail('The failed statement was ignored.');
    }

    #[Test]
    #[TestWith([\PDO::ERRMODE_SILENT])]
    #[TestWith([\PDO::ERRMODE_EXCEPTION])]
    public function shouldFailOnPrepareError(int $errorMode): void
    {
        $pdo = $this->createPdo($errorMode);

        $migration = $this->createMigration($pdo, [
            'INSERT INTO not_existing_table (id) VALUES (1)',
        ]);

        try {
            $migration->up();
        } catch (MigrationFailedException $error) {
            self::assertStringContainsString('"INSERT INTO not_existing_table (id) VALUES (1)"', $error->getMessage());
            self::assertStringContainsString('no such table: not_existing_table', $error->getMessage());

            return;
        }

        self::fail('The failed statement was ignored.');
    }

    private function createPdo(int $errorMode): \PDO
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, $errorMode);

        return $pdo;
    }

    private function createMigration(\PDO $pdo, array $sqls): AbstractPdoMigration
    {
        return new readonly class($pdo, $sqls) extends AbstractPdoMigration { // phpcs:ignore Symfony.Objects.ObjectInstantiation.Invalid
            public function __construct(\PDO $pdo, private array $sqls)
            {
                parent::__construct($pdo);
            }

            public function getDescription(): string
            {
                return 'test';
            }

            protected function doUp(): void
            {
                foreach ($this->sqls as $sql) {
                    $this->addSql($sql);
                }
            }

            protected function doDown(): void
            {
            }
        };
    }
}
