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

namespace FiveLab\Component\Migrator\History;

use FiveLab\Component\Migrator\MigrationExecutedState;
use FiveLab\Component\Migrator\MigrationMetadata;
use FiveLab\Component\Migrator\MigrationResult;

abstract class AbstractPdoMigrationsHistory implements MigrationsHistoryInterface
{
    private bool $initialized = false;

    public function __construct(protected readonly \PDO $pdo, private readonly string $tableName)
    {
    }

    final public function isExecuted(MigrationMetadata $metadata): bool
    {
        $this->initialize();

        $sql = \sprintf(
            'SELECT 1 FROM `%s` WHERE `group` = ? AND `version` = ?',
            $this->tableName
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$metadata->group, $metadata->version]);

        return (bool) $stmt->fetchColumn();
    }

    final public function get(MigrationMetadata $metadata): MigrationResult
    {
        $this->initialize();

        $sql = \sprintf(
            'SELECT * FROM `%s` WHERE `group` = ? AND `version` = ?',
            $this->tableName
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$metadata->group, $metadata->version]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            throw new \RuntimeException(\sprintf(
                'The migration "%s" does not exist in history in group "%s".',
                $metadata->version,
                $metadata->group ?? 'NULL'
            ));
        }

        return new MigrationResult(
            $metadata,
            MigrationExecutedState::Executed,
            new \DateTimeImmutable($row['executed_at']),
            (float) $row['execution_time'],
            $row['description']
        );
    }

    final public function add(MigrationResult $result): void
    {
        $this->initialize();

        $sql = \sprintf(
            'INSERT INTO `%s` (`group`, `version`, `fqcn`, `executed_at`, `execution_time`, `description`) VALUES (?, ?, ?, ?, ?, ?)',
            $this->tableName
        );

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            $result->metadata->group,
            $result->metadata->version,
            $result->metadata->class->getName(),
            $result->executedAt->format('Y-m-d H:i:s'),
            $result->executeTime,
            $result->description,
        ]);
    }

    final public function delete(MigrationMetadata $metadata): void
    {
        $this->initialize();

        $sql = \sprintf(
            'DELETE FROM `%s` WHERE `group` = ? AND `version` = ?',
            $this->tableName
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$metadata->group, $metadata->version]);
    }

    abstract protected function isMigrationTableExist(string $tableName): bool;

    abstract protected function getCreateTableSql(string $tableName): string;

    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        if (!$this->isMigrationTableExist($this->tableName)) {
            $sql = $this->getCreateTableSql($this->tableName);

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }

        $this->initialized = true;
    }
}
