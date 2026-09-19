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

namespace FiveLab\Component\Migrator\Tests\Unit;

use FiveLab\Component\Migrator\Factory\MigrationFactoryInterface;
use FiveLab\Component\Migrator\History\MigrationsHistoryInterface;
use FiveLab\Component\Migrator\MigrateDirection;
use FiveLab\Component\Migrator\MigrationExecutedState;
use FiveLab\Component\Migrator\MigrationExecutor;
use FiveLab\Component\Migrator\MigrationMetadata;
use FiveLab\Component\Migrator\MigrationResult;
use FiveLab\Component\Migrator\Tests\Migrations\DataSet06\A\Version1 as VersionA1;
use FiveLab\Component\Migrator\Tests\Migrations\DataSet06\B\Version1 as VersionB1;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MigrationExecutorTest extends TestCase
{
    #[Test]
    public function shouldReturnCurrentMetadataForSkippedMigration(): void
    {
        $metadata = new MigrationMetadata('default', '1', new \ReflectionClass(VersionB1::class));
        $storedMetadata = new MigrationMetadata('default', '1', new \ReflectionClass(VersionA1::class));
        $executedAt = new \DateTimeImmutable('2026-01-01 10:00:00');

        $history = $this->createStub(MigrationsHistoryInterface::class);
        $history->method('isExecuted')->willReturn(true);
        $history->method('get')->willReturn(new MigrationResult($storedMetadata, MigrationExecutedState::Executed, $executedAt, 1.5, 'stored'));

        $executor = new MigrationExecutor($history, $this->createStub(MigrationFactoryInterface::class));

        $result = $executor->execute($metadata, MigrateDirection::Up);

        self::assertSame($metadata, $result->metadata);
        self::assertSame(MigrationExecutedState::Skipped, $result->state);
        self::assertSame($executedAt, $result->executedAt);
    }
}
