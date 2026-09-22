## Russia has become a terrorist state.

<div style="font-size: 2em; color: #d0d7de;">
    <span style="background-color: #54aeff">&nbsp;#StandWith</span><span style="background-color: #d4a72c">Ukraine&nbsp;</span>
</div>

Migrator
========

[![Build Status](https://github.com/FiveLab/Migrator/workflows/Testing/badge.svg?branch=master)](https://github.com/FiveLab/Migrator/actions)
[![Latest Stable Version](https://poser.pugx.org/fivelab/migrator/v)](https://packagist.org/packages/fivelab/migrator)
[![Total Downloads](https://poser.pugx.org/fivelab/migrator/downloads)](https://packagist.org/packages/fivelab/migrator)
[![PHP Version Require](https://poser.pugx.org/fivelab/migrator/require/php)](https://packagist.org/packages/fivelab/migrator)
[![License](https://poser.pugx.org/fivelab/migrator/license)](https://packagist.org/packages/fivelab/migrator)

Run versioned migrations against **anything you can write PHP for**: SQL databases via PDO, ClickHouse,
search indices, brokers. The library is the runner — it finds migrations, orders them, keeps the history of
executed ones and ships console commands. What a migration does and where its history is stored is up to you.

```php
$migrator = new Migrator(
    new FilesystemMigrationsLocator(__DIR__.'/migrations', 'database'),
    new MigrationExecutor(
        new MySqlPdoMigrationsHistory($pdo, 'migration_versions'),
        new PdoMigrationFactory($pdo)
    )
);

$migrator->migrate(MigrateDirection::Up, null);
```

Why Migrator?
-------------

* **Not tied to a database or a framework.** A migration is a class with `up()` and `down()`. What runs
  inside is your code: SQL, an HTTP call to Elasticsearch, a ClickHouse query.
* **Independent groups.** MySQL schema, ClickHouse tables and index mappings live in separate groups
  with separate histories in one application.
* **Pluggable history.** Where executed migrations are stored is an interface with four methods.
* **Safe on deploy.** An optional lock keeps two processes (e.g. two pods) from applying the same
  migration twice.
* **Symfony integration** out of the box: configuration and console commands.

Installation
------------

```shell
composer require fivelab/migrator
```

Quick start
-----------

Migrations are plain classes named `VersionXXX`, where `XXX` is a number. They can live in any namespace
and in any subdirectory of the migrations directory:

```php
<?php // migrations/Version20260101120000.php

namespace App\Migrations;

use FiveLab\Component\Migrator\Migration\AbstractPdoMigration;

readonly class Version20260101120000 extends AbstractPdoMigration
{
    public function getDescription(): string
    {
        return 'Create the users table.';
    }

    protected function doUp(): void
    {
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL PRIMARY KEY, email VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO users (email) VALUES (:email)', ['email' => 'admin@example.com']);
    }

    protected function doDown(): void
    {
        $this->addSql('DROP TABLE users');
    }
}
```

Wire the runner and execute them:

```php
<?php

use FiveLab\Component\Migrator\Factory\PdoMigrationFactory;
use FiveLab\Component\Migrator\History\MySqlPdoMigrationsHistory;
use FiveLab\Component\Migrator\Locator\FilesystemMigrationsLocator;
use FiveLab\Component\Migrator\Lock\MySqlMigrationLock;
use FiveLab\Component\Migrator\MigrateDirection;
use FiveLab\Component\Migrator\MigrationExecutor;
use FiveLab\Component\Migrator\MigrationResult;
use FiveLab\Component\Migrator\Migrator;

$pdo = new \PDO('mysql:host=localhost;dbname=app', 'user', 'password');

$migrator = new Migrator(
    new FilesystemMigrationsLocator(__DIR__.'/migrations', 'database'),
    new MigrationExecutor(
        new MySqlPdoMigrationsHistory($pdo, 'migration_versions'),
        new PdoMigrationFactory($pdo)
    ),
    new MySqlMigrationLock($pdo)
);

$migrator->migrate(MigrateDirection::Up, null, static function (MigrationResult $result): void {
    \printf("%s: %s\n", $result->metadata->class->getShortName(), $result->state->name);
});
```

The history table is created automatically on the first run. Every migration is executed once: the already
executed ones are reported as `Skipped`.

Writing migrations
------------------

A migration class must be named `VersionXXX` (digits only) and declared as `readonly`, because the base
classes are readonly. Versions are compared as numbers, so `Version9` runs before `Version10`. Two
migrations with the same version in one group are a configuration error and the run fails.

### SQL through PDO

Extend `AbstractPdoMigration` and collect statements with `addSql()`. They are executed in the order they
were added, with bound parameters, and a failed statement aborts the migration:

```php
readonly class Version2 extends AbstractPdoMigration
{
    public function getDescription(): string
    {
        return 'Deactivate users without email.';
    }

    protected function doUp(): void
    {
        $this->addSql('UPDATE users SET active = 0 WHERE email IS NULL');
    }

    protected function doDown(): void
    {
        $this->addSql('UPDATE users SET active = 1 WHERE email IS NULL');
    }
}
```

There are no transactions around a migration. On MySQL a DDL statement commits implicitly anyway; on other
databases wrap the statements yourself if you need it.

### Any other storage

Extend `AbstractMigration` and do whatever you need. With `NativeMigrationFactory` the class is created
without arguments:

```php
readonly class Version3 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reindex the catalog.';
    }

    public function up(): void
    {
        // Any PHP code: an HTTP request, a queue declaration, a file operation.
    }

    public function down(): void
    {
    }
}
```

To pass a client into migrations, use a factory. `ClickHouseMigrationFactory` (requires
[smi2/phpclickhouse](https://github.com/smi2/phpClickHouse)) passes a `ClickHouseDB\Client`:

```php
readonly class Version4 extends AbstractMigration
{
    public function __construct(private Client $client)
    {
    }

    public function getDescription(): string
    {
        return 'Create the events table.';
    }

    public function up(): void
    {
        $this->client->write('CREATE TABLE events (id UInt32, created_at DateTime) ENGINE = MergeTree ORDER BY created_at');
    }

    public function down(): void
    {
        $this->client->write('DROP TABLE events');
    }
}
```

Your own factory is a class with one method — see `MigrationFactoryInterface`.

Console commands
----------------

```shell
bin/console migrations:migrate <group> [<version>] [--down]
bin/console migrations:execute <group> <version> [--down]
```

| Command                                  | Result                                                        |
|------------------------------------------|---------------------------------------------------------------|
| `migrations:migrate database`            | executes all migrations which are not executed yet            |
| `migrations:migrate database 05`         | executes migrations up to and **including** version `05`      |
| `migrations:migrate database --down`     | rolls back **all** executed migrations                        |
| `migrations:migrate database 05 --down`  | rolls back migrations down to and **including** version `05`  |
| `migrations:execute database 05`         | executes only version `05`                                    |
| `migrations:execute database 05 --down`  | rolls back only version `05`                                  |

Both commands ask for a confirmation in the interactive mode. In a deploy script run them with `-n`.

Symfony
-------

Register the extension in the kernel:

```php
// src/Kernel.php

use FiveLab\Component\Migrator\DependencyInjection\MigratorExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

protected function build(ContainerBuilder $container): void
{
    $container->registerExtension(new MigratorExtension());
}
```

Describe the migrators and the services they use:

```yaml
# config/packages/fivelab_migrator.yaml

fivelab_migrator:
    migrations:
        database:
            path:    '%kernel.project_dir%/migrations/database'
            factory: app.migrations.pdo_factory
            history: app.migrations.history
            lock:    app.migrations.lock

        clickhouse:
            path:    '%kernel.project_dir%/migrations/clickhouse'
            factory: app.migrations.clickhouse_factory
            history: app.migrations.history
```

```yaml
# config/services.yaml

services:
    app.migrations.pdo_factory:
        class: FiveLab\Component\Migrator\Factory\PdoMigrationFactory
        arguments: ['@app.pdo']

    app.migrations.history:
        class: FiveLab\Component\Migrator\History\MySqlPdoMigrationsHistory
        arguments: ['@app.pdo', 'migration_versions']

    app.migrations.lock:
        class: FiveLab\Component\Migrator\Lock\MySqlMigrationLock
        arguments: ['@app.pdo']
```

| Option    | Required | Description                                                                      |
|-----------|----------|----------------------------------------------------------------------------------|
| `path`    | yes      | Directory with migrations, scanned recursively                                    |
| `history` | yes      | Service id of the history                                                         |
| `factory` | no       | Service id of the factory, `NativeMigrationFactory` by default                    |
| `lock`    | no       | Service id of the lock, no lock by default                                        |
| `group`   | no       | Group name used in the console and in the history, the configuration key by default |

The commands `migrations:migrate` and `migrations:execute` are registered automatically.

History storage
---------------

Executed migrations are stored by an implementation of `MigrationsHistoryInterface`. The package ships
`MySqlPdoMigrationsHistory`, which keeps them in a MySQL table (it is created on the first run):

| `group`  | `version`        | `fqcn`                                   | `executed_at`       | `execution_time` | `description`           |
|----------|------------------|------------------------------------------|---------------------|------------------|-------------------------|
| database | 20260101120000   | App\Migrations\Version20260101120000     | 2026-01-01 12:05:31 | 0.153            | Create the users table. |

Storing the history somewhere else means implementing four methods:

```php
interface MigrationsHistoryInterface
{
    public function isExecuted(MigrationMetadata $metadata): bool;

    public function get(MigrationMetadata $metadata): MigrationResult;

    public function add(MigrationResult $result): void;

    public function delete(MigrationMetadata $metadata): void;
}
```

The history is independent from what the migrations do: ClickHouse migrations can keep their history in
MySQL, and the other way around.

Parallel runs
-------------

Several instances of an application often start migrations at the same time on deploy. Without a lock both
of them see a migration as not executed and apply it twice. Pass a lock to the migrator to prevent this:

```php
new Migrator($locator, $executor, new MySqlMigrationLock($pdo, 'app_migrations', 60));
```

`MySqlMigrationLock` uses MySQL named locks (`GET_LOCK`), so the lock is released automatically when the
process dies. The second process waits for the given timeout and then fails. Own lock — implement
`MigrationLockInterface`.

Compared to other tools
-----------------------

|                             | Migrator                | [doctrine/migrations](https://github.com/doctrine/migrations) | [phinx](https://github.com/cakephp/phinx) | [phpmig](https://github.com/davedevelopment/phpmig) |
|-----------------------------|-------------------------|-----------------------|--------------------|---------------------|
| What a migration does       | any PHP code            | any PHP code, DBAL API | SQL, table builder | any PHP code        |
| Where the history is stored | any (interface)         | the DBAL connection   | the database        | any (adapters)      |
| Independent groups          | yes                     | one set per configuration | one set per environment | one set        |
| Lock for parallel runs      | yes, for MySQL          | no (a separate bundle) | no                 | no                  |
| Last release                | maintained              | maintained            | maintained          | 2020                |

Migrator is the right tool when migrations are not only a relational schema: ClickHouse tables, search
index mappings, broker topology. For a Doctrine ORM schema, `doctrine/migrations` with its diff generation
is a better fit.

### Migrating from phpmig

| phpmig                                         | Migrator                                               |
|------------------------------------------------|--------------------------------------------------------|
| `Phpmig\Migration\Migration` with `up()`/`down()` | `AbstractMigration` or `AbstractPdoMigration`          |
| Adapters (`File\Flat`, `PDO\Sql`, `Mongo`, ...)  | Implementations of `MigrationsHistoryInterface`         |
| `phpmig.php` with a container                    | Wiring in your code or the Symfony configuration        |
| `phpmig migrate` / `phpmig rollback`             | `migrations:migrate <group>` / `migrations:migrate <group> --down` |
| `phpmig up <version>` / `phpmig down <version>`  | `migrations:execute <group> <version>` / `... --down`   |
| `phpmig status`, `phpmig generate`               | Not implemented yet                                     |

Migration file names are free in both tools; in Migrator the class name carries the version (`VersionXXX`).

Development
-----------

For easy development you can use the `Docker` and `docker compose`.

```shell
docker compose up -d
docker compose exec php bash
```

Inside the container:

```shell
bin/phpunit
bin/phpstan
bin/phpcs --standard=src/phpcs-ruleset.xml -n src/
bin/phpcs --standard=tests/phpcs-ruleset.xml -n tests/
```

The functional tests drop **all** tables in the database from `MYSQL_DSN`, so point it to a dedicated test
database. Without the `MYSQL_*` variables they are skipped.
