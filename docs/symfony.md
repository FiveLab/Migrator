Symfony
=======

The [README](../README.md#symfony) shows how to register the extension and configure migrators. This page
covers what happens behind it.

Registered services
-------------------

For every entry of `fivelab_migrator.migrations` the extension registers three services:

| Service id                             | Class                         |
|----------------------------------------|-------------------------------|
| `migrations.migrator.<name>`           | `Migrator`                    |
| `migrations.migrator.<name>.locator`   | `FilesystemMigrationsLocator` |
| `migrations.migrator.<name>.executor`  | `MigrationExecutor`           |

and the shared ones:

| Service id                                                   | Description                                               |
|--------------------------------------------------------------|-----------------------------------------------------------|
| `migrations.migrator_registry`                               | Returns a migrator by its group, alias `MigratorRegistry` |
| `migrations.console.migrate`                                 | The `migrations:migrate` command                          |
| `migrations.console.execute_migration`                       | The `migrations:execute` command                          |
| `FiveLab\Component\Migrator\Factory\NativeMigrationFactory`  | The default factory                                       |

The history, the lock and a custom factory are your services: the configuration only references them by id.
Each group must be unique, otherwise the container fails to build.

Several groups
--------------

Groups are independent: each has its own directory, factory, history and lock. They may share the same
history service — its rows are keyed by the group:

```yaml
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
            lock:    app.migrations.lock

services:
    app.migrations.clickhouse_factory:
        class: FiveLab\Component\Migrator\Factory\ClickHouseMigrationFactory
        arguments: ['@app.clickhouse_client']
```

With a shared lock the groups never run at the same time. If they should, give each group its own lock with
a different name (the second argument of `MySqlMigrationLock`).

Running migrations from code
----------------------------

The registry returns the migrator of a group, for example to prepare the database in functional tests:

```php
use FiveLab\Component\Migrator\MigrateDirection;
use FiveLab\Component\Migrator\MigratorRegistry;

self::getContainer()->get(MigratorRegistry::class)->get('database')->migrate(MigrateDirection::Up, null);
```

Outside of tests inject `MigratorRegistry` with autowiring. Prefer the console commands for deployments —
see [Deployment](deployment.md).

Console commands without the framework
--------------------------------------

The commands need only a `MigratorRegistry`, which accepts any PSR-11 container. So they work in a plain
Symfony Console application as well:

```php
<?php

use FiveLab\Component\Migrator\Console\ExecuteMigrationCommand;
use FiveLab\Component\Migrator\Console\MigrateCommand;
use FiveLab\Component\Migrator\MigratorRegistry;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ServiceLocator;

$registry = new MigratorRegistry(new ServiceLocator([
    'database'   => static fn () => $databaseMigrator,
    'clickhouse' => static fn () => $clickhouseMigrator,
]));

$application = new Application('migrations');
$application->addCommands([new MigrateCommand($registry), new ExecuteMigrationCommand($registry)]);
$application->run();
```

The keys of the container are the groups, the values are `Migrator` objects built as in the
[quick start](../README.md#quick-start).
