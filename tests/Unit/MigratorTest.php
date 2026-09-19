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

use FiveLab\Component\Migrator\Locator\MigrationsLocatorInterface;
use FiveLab\Component\Migrator\MigrateDirection;
use FiveLab\Component\Migrator\MigrationExecutedState;
use FiveLab\Component\Migrator\MigrationExecutorInterface;
use FiveLab\Component\Migrator\MigrationMetadata;
use FiveLab\Component\Migrator\MigrationResult;
use FiveLab\Component\Migrator\Migrator;
use FiveLab\Component\Migrator\Tests\Migrations\DataSet05\Gamma\Version1;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MigratorTest extends TestCase
{
    #[Test]
    public function shouldMigrateUpToZeroVersion(): void
    {
        $locator = $this->createStub(MigrationsLocatorInterface::class);

        $locator->method('locate')
            ->willReturn([
                $this->createMetadata('0'),
                $this->createMetadata('1'),
            ]);

        $executor = $this->createMock(MigrationExecutorInterface::class);

        $executor->expects($this->once())
            ->method('execute')
            ->with(self::callback(static fn(MigrationMetadata $metadata): bool => '0' === $metadata->version), MigrateDirection::Up)
            ->willReturnCallback(static fn(MigrationMetadata $metadata): MigrationResult => new MigrationResult(
                $metadata,
                MigrationExecutedState::Executed,
                new \DateTimeImmutable(),
                0.0,
                null
            ));

        $migrator = new Migrator($locator, $executor);

        $results = \iterator_to_array($migrator->migrate(MigrateDirection::Up, '0'));

        self::assertCount(1, $results);
    }

    private function createMetadata(string $version): MigrationMetadata
    {
        return new MigrationMetadata('default', $version, new \ReflectionClass(Version1::class));
    }
}
