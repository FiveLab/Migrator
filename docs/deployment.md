Deployment
==========

Run migrations as a separate step
---------------------------------

Run migrations once per deploy, before the new version receives traffic, and without questions:

```shell
bin/console migrations:migrate database -n
```

The command returns `0` on success. If a migration fails, the exception is printed and the command returns
a non-zero code, so the deploy stops.

* **CI/CD**: a job after the build and before the switch of traffic.
* **Kubernetes**: a `Job` (or a Helm hook) which runs the command once. If you run migrations in the
  entrypoint or an init container of every pod instead, several pods start them at the same time — use a
  [lock](#the-lock).
* Run one command per group. Groups are independent, so the order of the commands is up to you.

Never pass `--down` without a version in a deploy script: it rolls back all migrations of the group.

The lock
--------

Without a lock two processes which start at the same time both see a migration as not executed and both
apply it. For schema changes the second one usually fails; for data changes (`UPDATE ... SET x = x + 1`)
the data are changed twice. Configure a lock for every migrator which may run in parallel:

```php
new Migrator($locator, $executor, new MySqlMigrationLock($pdo, 'app_migrations', 600));
```

`MySqlMigrationLock` waits up to the timeout (the third argument, 60 seconds by default) and then fails.
Choose a timeout longer than your longest run of migrations: the second process waits until the first one
finishes all of them and then reports them as skipped. The lock is bound to the connection, so if the
process dies, MySQL releases it.

When a migration fails
----------------------

The library does not wrap migrations into transactions. After a failure:

* the statements executed before the failed one stay applied (on MySQL a DDL statement can't be rolled back
  anyway);
* the failed migration is **not** written into the history, so the next run starts from it again;
* the migrations executed before it in the same run are recorded and printed by `migrations:migrate`.

To recover:

1. Look at what the failed migration managed to change.
2. Either undo it by hand and fix the migration, or make the migration able to continue from the partial
   state (`CREATE TABLE IF NOT EXISTS`, `DROP ... IF EXISTS`).
3. Run `migrations:migrate` again.

A single migration can be rolled back with `migrations:execute <group> <version> --down`. There is no
command which marks a migration as executed without running it yet; until then insert the row into the
history table by hand:

```sql
INSERT INTO migration_versions (`group`, `version`, `fqcn`, `executed_at`, `execution_time`, `description`)
VALUES ('database', '20260101120000', 'App\\Migrations\\Version20260101120000', NOW(), 0, 'Marked by hand');
```

Migrations which survive a failure
----------------------------------

* One schema change per migration: a failure in the middle then leaves nothing half-done.
* Prefer statements which can be repeated: `CREATE TABLE IF NOT EXISTS`, `DROP ... IF EXISTS`.
* Change large tables in batches, and keep data migrations separate from schema migrations.
* Write `down()` right away, even if you never plan to roll back: it is the fastest way back when a release
  goes wrong.

Limitations
-----------

What the library does not do yet:

* no `status`, `generate` and `--dry-run` commands, no command to mark a migration as executed;
* only the MySQL history ships with the library — other storages are an interface away
  (see [Extending](extending.md#own-history-storage));
* only the MySQL lock ships with the library — for other storages see the
  [symfony/lock adapter](extending.md#own-lock);
* no transactions around migrations;
* no diff generation from an ORM mapping: for a Doctrine ORM schema use `doctrine/migrations`.
