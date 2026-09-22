CHANGELOG
=========

v1.1.0
------

* Order migrations by version instead of the file path and compare versions as numbers: migrating up to
  version `9` no longer executes `Version10`. The target version `0` is supported.
* Fail on duplicate migration versions in a group (including `Version1` and `Version01`) instead of silently
  skipping one of the migrations.
* Fail on SQL errors regardless of the PDO error mode (`ERRMODE_SILENT`/`ERRMODE_WARNING` ignored them), and
  wrap errors of `prepare()` into `MigrationFailedException`.
* Add `MigrationLockInterface` and `MySqlMigrationLock` (`GET_LOCK`) to prevent parallel execution of
  migrations, and the `lock` option of a migrator in the Symfony configuration.
* Create the MySQL history table with `CREATE TABLE IF NOT EXISTS` and check it via `information_schema`
  (works with `sql_mode=ANSI_QUOTES`).
* Parse migration files with the tokenizer. Skip traits, interfaces and helper classes in the migrations
  directory. Fail with a clear message if the directory does not exist.
* Register the default `NativeMigrationFactory` service in the Symfony integration and require unique
  migrator groups.
* Add an optional callback to `MigratorInterface::migrate()`, called right after each migration.
  `migrations:migrate` prints the progress and reports when there is nothing to execute.
  **BC:** custom implementations of `MigratorInterface` must add the parameter.
* Require `psr/container` (used by `MigratorRegistry`). Support Symfony 8.

v1.0.2
------

* Add `null` to signature of `FiveLab\Component\Migrator\Migration\AbstractPdoMigration::addSql`


v1.0.1
------

* Add ClickHouse migration factory based on `smi2/phpclickhouse` client. 

v1.0.0
------

* Initialize library with PDO migration factory and MySQL history adapter.
