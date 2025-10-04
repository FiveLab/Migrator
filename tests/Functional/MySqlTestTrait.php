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

namespace FiveLab\Component\Migrator\Tests\Functional;

use FiveLab\Component\Migrator\Factory\PdoMigrationFactory;
use FiveLab\Component\Migrator\History\MySqlPdoMigrationsHistory;
use FiveLab\Component\Migrator\Locator\FilesystemMigrationsLocator;
use FiveLab\Component\Migrator\MigrationExecutor;
use FiveLab\Component\Migrator\Migrator;
use FiveLab\Component\Migrator\MigratorRegistry;
use Symfony\Component\DependencyInjection\Container;

/**
 * @method void markTestSkipped(string $message)
 */
trait MySqlTestTrait
{
    protected \PDO $pdo;
    protected MySqlPdoMigrationsHistory $history;
    protected PdoMigrationFactory $factory;
    protected MigrationExecutor $executor;

    protected function setUp(): void
    {
        if (!\getenv('MYSQL_DSN') || !\getenv('MYSQL_USER') || !\getenv('MYSQL_PASSWORD')) {
            self::markTestSkipped('Missed MYSQL_DSN or MYSQL_USER or MYSQL_PASSWORD environment variable.');
        }

        $this->pdo = new \PDO(\getenv('MYSQL_DSN'), \getenv('MYSQL_USER'), \getenv('MYSQL_PASSWORD'));

        $this->history = new MySqlPdoMigrationsHistory($this->pdo, 'migration_versions');
        $this->factory = new PdoMigrationFactory($this->pdo);
        $this->executor = new MigrationExecutor($this->history, $this->factory);
    }

    protected function dropTables(): void
    {
        $tables = $this->getTableNames();

        foreach ($tables as $tableName) {
            $this->executeSql('DROP TABLE `'.$tableName.'`');
        }
    }

    protected function getTableNames(): array
    {
        $tables = $this->executeSql('SHOW TABLES');

        return \array_map(static fn(array $entry) => \array_values($entry)[0], $tables);
    }

    protected function executeSql(string $sql): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function createMigratorRegistry(): MigratorRegistry
    {
        $locator = new FilesystemMigrationsLocator(__DIR__.'/../Migrations/DataSet02', 'Database');

        $migrator = new Migrator($locator, $this->executor);

        $container = new Container();
        $container->set('Database', $migrator);

        return new MigratorRegistry($container);
    }
}
