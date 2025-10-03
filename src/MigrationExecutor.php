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

namespace FiveLab\Component\Migrator;

use FiveLab\Component\Migrator\Factory\MigrationFactoryInterface;
use FiveLab\Component\Migrator\History\MigrationsHistoryInterface;
use FiveLab\Component\Migrator\Migration\DescribedMigrationInterface;

readonly class MigrationExecutor implements MigrationExecutorInterface
{
    public function __construct(private MigrationsHistoryInterface $history, private MigrationFactoryInterface $factory)
    {
    }

    public function execute(MigrationMetadata $metadata, MigrateDirection $direction): MigrationResult
    {
        if (MigrateDirection::Up === $direction && $this->history->isExecuted($metadata)) {
            $result = $this->history->get($metadata);

            return new MigrationResult($result->metadata, MigrationExecutedState::Skipped, $result->executedAt, $result->executeTime, $result->description);
        }

        if (MigrateDirection::Down === $direction && !$this->history->isExecuted($metadata)) {
            $description = $metadata instanceof DescribedMigrationInterface ? $metadata->getDescription() : null;

            return new MigrationResult($metadata, MigrationExecutedState::Skipped, new \DateTimeImmutable(), 0, $description);
        }

        $migration = $this->factory->create($metadata);

        $startTime = \microtime(true);

        match ($direction) {
            MigrateDirection::Up   => $migration->up(),
            MigrateDirection::Down => $migration->down(),
        };

        $executeTime = \microtime(true) - $startTime;

        $result = new MigrationResult(
            $metadata,
            MigrationExecutedState::Executed,
            new \DateTimeImmutable(),
            $executeTime,
            $migration instanceof DescribedMigrationInterface ? $migration->getDescription() : null
        );

        match ($direction) {
            MigrateDirection::Up   => $this->history->add($result),
            MigrateDirection::Down => $this->history->delete($metadata),
        };

        return $result;
    }
}
