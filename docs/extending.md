Extending
=========

Every part of a run is an interface, so it can be replaced with your own implementation. See
[Concepts](concepts.md) for what each part is responsible for.

Own history storage
-------------------

`MigrationsHistoryInterface` has four methods. A history which keeps executed migrations in a JSON file:

```php
<?php

use FiveLab\Component\Migrator\History\MigrationsHistoryInterface;
use FiveLab\Component\Migrator\MigrationExecutedState;
use FiveLab\Component\Migrator\MigrationMetadata;
use FiveLab\Component\Migrator\MigrationResult;

readonly class JsonFileMigrationsHistory implements MigrationsHistoryInterface
{
    public function __construct(private string $file)
    {
    }

    public function isExecuted(MigrationMetadata $metadata): bool
    {
        return \array_key_exists($this->key($metadata), $this->read());
    }

    public function get(MigrationMetadata $metadata): MigrationResult
    {
        $row = $this->read()[$this->key($metadata)] ?? throw new \RuntimeException(\sprintf(
            'The migration "%s" is not executed.',
            $metadata->version
        ));

        return new MigrationResult(
            $metadata,
            MigrationExecutedState::Executed,
            new \DateTimeImmutable($row['executed_at']),
            (float) $row['execution_time'],
            $row['description']
        );
    }

    public function add(MigrationResult $result): void
    {
        $rows = $this->read();

        $rows[$this->key($result->metadata)] = [
            'fqcn'           => $result->metadata->class->getName(),
            'executed_at'    => $result->executedAt->format(\DATE_ATOM),
            'execution_time' => $result->executeTime,
            'description'    => $result->description,
        ];

        $this->write($rows);
    }

    public function delete(MigrationMetadata $metadata): void
    {
        $rows = $this->read();
        unset($rows[$this->key($metadata)]);

        $this->write($rows);
    }

    private function key(MigrationMetadata $metadata): string
    {
        return $metadata->group.':'.$metadata->version;
    }

    private function read(): array
    {
        if (!\is_file($this->file)) {
            return [];
        }

        return \json_decode((string) \file_get_contents($this->file), true, 512, \JSON_THROW_ON_ERROR);
    }

    private function write(array $rows): void
    {
        \file_put_contents($this->file, \json_encode($rows, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR), \LOCK_EX);
    }
}
```

The executor decides when the history is called: it checks `isExecuted()` before a migration, calls `get()`
only to report an already executed one on up, and calls `add()` (up) or `delete()` (down) after a migration.
If the history loses a row, the migration runs again, so keep the storage durable. A file is fine for a single
server; with several instances use a shared storage and a [lock](#own-lock).

### Another SQL database

`AbstractPdoMigrationsHistory` implements the queries and leaves two methods: how to check that the table
exists and how to create it. The queries quote identifiers with backticks, which works for MySQL, MariaDB
and SQLite, but not for PostgreSQL — for it implement `MigrationsHistoryInterface` directly. SQLite:

```php
<?php

use FiveLab\Component\Migrator\History\AbstractPdoMigrationsHistory;

class SqlitePdoMigrationsHistory extends AbstractPdoMigrationsHistory
{
    protected function isMigrationTableExist(string $tableName): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM sqlite_master WHERE type = \'table\' AND name = ?');
        $stmt->execute([$tableName]);

        return (bool) $stmt->fetchColumn();
    }

    protected function getCreateTableSql(string $tableName): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS `{$tableName}` (
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
}
```

Keep `IF NOT EXISTS`: several processes may create the table at the same time on the first run.

Own factory
-----------

A factory creates a migration from its metadata. Use it to pass clients and services into migrations.

### Recipe: Elasticsearch/OpenSearch indices

The factory passes the client into every migration:

```php
<?php

use FiveLab\Component\Migrator\Factory\MigrationFactoryInterface;
use FiveLab\Component\Migrator\Migration\MigrationInterface;
use FiveLab\Component\Migrator\MigrationMetadata;
use OpenSearch\Client;

readonly class SearchMigrationFactory implements MigrationFactoryInterface
{
    public function __construct(private Client $client)
    {
    }

    public function create(MigrationMetadata $metadata): MigrationInterface
    {
        return $metadata->class->newInstance($this->client);
    }
}
```

A migration creates a versioned index and points an alias to it, so the application always works with the
alias:

```php
<?php

namespace App\Migrations\Search;

use FiveLab\Component\Migrator\Migration\AbstractMigration;
use OpenSearch\Client;

readonly class Version20260101120000 extends AbstractMigration
{
    public function __construct(private Client $client)
    {
    }

    public function getDescription(): string
    {
        return 'Create the products index.';
    }

    public function up(): void
    {
        $this->client->indices()->create([
            'index' => 'products_v1',
            'body'  => [
                'mappings' => [
                    'properties' => [
                        'name'  => ['type' => 'text'],
                        'price' => ['type' => 'scaled_float', 'scaling_factor' => 100],
                    ],
                ],
            ],
        ]);

        $this->client->indices()->putAlias(['index' => 'products_v1', 'name' => 'products']);
    }

    public function down(): void
    {
        $this->client->indices()->delete(['index' => 'products_v1']);
    }
}
```

A later mapping change which is not compatible with the existing one follows the same pattern: create
`products_v2`, copy the documents with `reindex`, then move the alias with a single `updateAliases` call —
the application never sees a missing index. The official Elasticsearch client
(`Elastic\Elasticsearch\Client`) has the same `indices()` API.

The history of such migrations can live in the database of the application (`MySqlPdoMigrationsHistory`):
a search engine is a poor place for a small table which must never lose a row.

Own locator
-----------

`FilesystemMigrationsLocator` scans a directory. If the migrations are known in advance — registered as
services or listed in a configuration — implement `MigrationsLocatorInterface`:

```php
<?php

use FiveLab\Component\Migrator\Locator\MigrationsLocatorInterface;
use FiveLab\Component\Migrator\MigrateDirection;
use FiveLab\Component\Migrator\Migration\MigrationInterface;
use FiveLab\Component\Migrator\MigrationMetadata;

readonly class ClassListMigrationsLocator implements MigrationsLocatorInterface
{
    /**
     * Constructor.
     *
     * @param string                                  $group
     * @param array<class-string<MigrationInterface>> $classes
     */
    public function __construct(private string $group, private array $classes)
    {
    }

    public function locate(MigrateDirection $direction): iterable
    {
        $migrations = [];

        foreach ($this->classes as $class) {
            \preg_match('/Version(\d+)$/', $class, $matches);

            $migrations[] = new MigrationMetadata($this->group, $matches[1], new \ReflectionClass($class));
        }

        \usort($migrations, static fn(MigrationMetadata $a, MigrationMetadata $b): int => \strnatcmp($a->version, $b->version));

        return MigrateDirection::Down === $direction ? \array_reverse($migrations) : $migrations;
    }
}
```

The locator is responsible for the order: ascending versions for up, descending for down. It should also
reject duplicate versions, as `FilesystemMigrationsLocator` does.

Own lock
--------

`MigrationLockInterface` has two methods: `acquire()` and `release()`. `MySqlMigrationLock` covers MySQL.
For other storages an adapter to [symfony/lock](https://symfony.com/doc/current/components/lock.html) gives
you Redis, PostgreSQL advisory locks, files and more:

```php
<?php

use FiveLab\Component\Migrator\Lock\MigrationLockInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

class SymfonyMigrationLock implements MigrationLockInterface
{
    private ?SharedLockInterface $lock = null;

    public function __construct(private readonly LockFactory $factory, private readonly string $name = 'migrations')
    {
    }

    public function acquire(): void
    {
        $this->lock = $this->factory->createLock($this->name, 3600);

        if (!$this->lock->acquire(true)) {
            throw new \RuntimeException(\sprintf('Can\'t acquire the migration lock "%s".', $this->name));
        }
    }

    public function release(): void
    {
        $this->lock?->release();
        $this->lock = null;
    }
}
```

The TTL (`3600` seconds here) must be longer than the longest run, otherwise the lock expires while the
migrations are still being executed.
