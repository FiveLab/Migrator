Concepts
========

The library splits a migration run into small parts, and each of them can be replaced.

| Part     | Interface                    | Answers                                              |
|----------|------------------------------|------------------------------------------------------|
| Locator  | `MigrationsLocatorInterface` | Which migrations exist and in which order?           |
| Factory  | `MigrationFactoryInterface`  | How is a migration object created?                   |
| Migration| `MigrationInterface`         | What do `up()` and `down()` do?                      |
| History  | `MigrationsHistoryInterface` | Which migrations are already executed?               |
| Lock     | `MigrationLockInterface`     | May this process run migrations right now?           |
| Executor | `MigrationExecutorInterface` | Executes one migration and records it in the history |
| Migrator | `MigratorInterface`          | Runs the whole set: `migrate()` and `execute()`      |

Group
-----

A group is a named set of migrations with its own directory: `database`, `clickhouse`, `search`. The group
is passed to the locator, stored in the history next to the version and used as the argument of the console
commands. Groups never interfere with each other, so one application can migrate a MySQL schema,
ClickHouse tables and index mappings independently.

Version
-------

The version is taken from the class name: `Version20260101120000` has the version `20260101120000`. Only
digits are allowed after `Version`, and the namespace does not matter — the file may live in any
subdirectory of the migrations directory.

Versions are compared as numbers, not as strings:

* `Version9` is executed before `Version10`;
* `migrations:migrate database 9` does not execute `Version10`;
* `Version1` and `Version01` are the same version. Two migrations with the same version in one group are
  a configuration error: the run fails with the list of the conflicting classes, because the history could
  not tell them apart.

There is no generator yet, so pick a scheme and keep it. A timestamp (`Version20260101120000`) works better
in a team than a counter: two developers rarely pick the same second.

Migration
---------

A migration is a class with `up()` and `down()`. `AbstractMigration` also requires `getDescription()`:
the description is stored in the history and helps to read it later. `AbstractPdoMigration` collects SQL
statements with `addSql()` and executes them for you.

Both base classes are `readonly`, so a migration which extends them must be declared `readonly` as well:

```php
readonly class Version1 extends AbstractPdoMigration
```

PHP checks it when the file is loaded, and the error is fatal: it stops the whole run, not only one
migration.

Files which are not migrations — abstract base classes, traits, interfaces, enums, helper classes — may live
next to migrations, they are skipped. A class named `VersionXXX` which does not implement `MigrationInterface`, and a migration whose name
does not match `VersionXXX`, are errors: skipping them silently would hide a real migration.

History
-------

The history stores what was executed. The key is the pair of the group and the version; the row also keeps
the class name, the time, the duration and the description.

The history is independent from what a migration does. ClickHouse migrations can store their history in
MySQL, and nothing stops you from writing an implementation for the storage you migrate — see
[Extending](extending.md).

What happens during a run
-------------------------

`migrate(MigrateDirection $direction, ?string $toVersion, ?callable $onResult = null)`:

1. If `$toVersion` is given, the locator is wrapped into a filter: `<=` for up, `>=` for down.
2. The lock is acquired, if the migrator has one.
3. The locator reads the directory, builds the metadata of every migration, orders them by version
   (reversed for down) and checks that the versions are unique.
4. For every migration the executor:
    * up: if the history knows it — reports `Skipped`; otherwise creates the migration through the factory,
      calls `up()`, measures the time and writes the row into the history;
    * down: if the history does not know it — reports `Skipped`; otherwise calls `down()` and deletes the
      row from the history.
5. The callback, if given, is called right after every migration. The console uses it to print the
   progress, so the output shows what was applied even if a later migration fails.
6. The lock is released, even when a migration fails.

`execute(MigrateDirection $direction, string $version)` does the same for a single version and fails if
the version does not exist.

Both methods return `MigrationResult` objects: the metadata, the state (`Executed` or `Skipped`), the time,
the duration and the description.

Direction and the version argument
----------------------------------

The version argument means "up to and including this version" in both directions, so it is not symmetric:

* `migrate(Up, '05')` leaves versions `01`–`05` applied;
* `migrate(Down, '05')` rolls back everything down to `05`, `05` included, and leaves `01`–`04` applied.

`migrate(Down, null)` rolls back **everything**, and in the non-interactive mode the console does not ask
for a confirmation. Never pass `--down` without a version in a deploy script.
