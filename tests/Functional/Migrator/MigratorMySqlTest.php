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

namespace FiveLab\Component\Migrator\Tests\Functional\Migrator;

use FiveLab\Component\Migrator\Exception\MigrationFailedException;
use FiveLab\Component\Migrator\Locator\FilesystemMigrationsLocator;
use FiveLab\Component\Migrator\MigrateDirection;
use FiveLab\Component\Migrator\Migrator;
use FiveLab\Component\Migrator\Tests\Functional\MySqlTestTrait;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MigratorMySqlTest extends TestCase
{
    use MySqlTestTrait {
        setUp as protected setUpMySql;
    }

    private Migrator $migrator; // phpcs:ignore

    protected function setUp(): void
    {
        $this->setUpMySql();

        $this->migrator = $this->createMigrator(__DIR__.'/../../Migrations/DataSet02', 'Database');
    }

    protected function tearDown(): void
    {
        $this->dropTables();
    }

    protected function createMigrator(string $path, string $group): Migrator
    {
        $locator = new FilesystemMigrationsLocator($path, $group);

        return new Migrator($locator, $this->executor);
    }

    #[Test]
    public function shouldSuccessUp(): void
    {
        $this->migrator->migrate(MigrateDirection::Up, null);

        $rows = $this->executeSql('SELECT * FROM test_1 ORDER BY id ASC');

        self::assertEquals([
            ['id' => 1, 'label' => 'Bla Bla', 'active' => 1],
            ['id' => 2, 'label' => 'Foo Bar', 'active' => 1],
        ], $rows);
    }

    #[Test]
    #[Depends('shouldSuccessUp')]
    public function shouldSuccessDown(): void
    {
        $this->migrator->migrate(MigrateDirection::Up, null);
        $this->migrator->migrate(MigrateDirection::Down, null);

        $result = $this->getTableNames();

        self::assertEquals(['migration_versions'], $result);
    }

    #[Test]
    #[Depends('shouldSuccessUp')]
    public function shouldSuccessUpToSpecificVersion(): void
    {
        $this->migrator->migrate(MigrateDirection::Up, '02');

        $rows = $this->executeSql('SELECT * FROM test_1 ORDER BY id ASC');

        self::assertEquals([
            ['id' => 1, 'label' => 'Bla Bla'],
            ['id' => 2, 'label' => 'Foo Bar'],
        ], $rows);
    }

    #[Test]
    #[Depends('shouldSuccessUp')]
    public function shouldSuccessDownToSpecificVersion(): void
    {
        $this->migrator->migrate(MigrateDirection::Up, null);
        $this->migrator->migrate(MigrateDirection::Down, '03');

        $rows = $this->executeSql('SELECT * FROM test_1 ORDER BY id ASC');

        self::assertEquals([
            ['id' => 1, 'label' => 'Bla Bla'],
            ['id' => 2, 'label' => 'Foo Bar'],
        ], $rows);
    }

    #[Test]
    #[Depends('shouldSuccessUpToSpecificVersion')]
    public function shouldSuccessExecuteSpecificVersion(): void
    {
        $this->migrator->migrate(MigrateDirection::Up, null);
        $this->migrator->execute(MigrateDirection::Up, '03');

        $rows = $this->executeSql('SELECT * FROM test_1 ORDER BY id ASC');

        self::assertEquals([
            ['id' => 1, 'label' => 'Bla Bla', 'active' => 1],
            ['id' => 2, 'label' => 'Foo Bar', 'active' => 1],
        ], $rows);

        $this->migrator->execute(MigrateDirection::Down, '03');

        $rows = $this->executeSql('SELECT * FROM test_1 ORDER BY id ASC');

        self::assertEquals([
            ['id' => 1, 'label' => 'Bla Bla'],
            ['id' => 2, 'label' => 'Foo Bar'],
        ], $rows);
    }

    #[Test]
    public function shouldSuccessWrapPdoException(): void
    {
        $migrator = $this->createMigrator(__DIR__.'/../../Migrations/DataSet03', 'Database');

        $this->expectException(MigrationFailedException::class);
        $this->expectExceptionMessage('Migration failed - "SELECT * FROM not_existing_table WHERE label = :foo", parameters: {"foo":"bar"} with message: SQLSTATE[42S02]: Base table or view not found:');

        $migrator->migrate(MigrateDirection::Up, null);
    }
}
