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

use FiveLab\Component\Migrator\Lock\MigrationLockInterface;
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

    #[Test]
    public function shouldCallCallbackRightAfterEachMigration(): void
    {
        $locator = $this->createStub(MigrationsLocatorInterface::class);
        $locator->method('locate')->willReturn([$this->createMetadata('1'), $this->createMetadata('2')]);

        $executor = $this->createStub(MigrationExecutorInterface::class);

        $executor->method('execute')
            ->willReturnCallback(static function (MigrationMetadata $metadata): MigrationResult {
                if ('2' === $metadata->version) {
                    throw new \RuntimeException('Migration failed.');
                }

                return new MigrationResult($metadata, MigrationExecutedState::Executed, new \DateTimeImmutable(), 0.0, null);
            });

        $reported = [];

        try {
            (new Migrator($locator, $executor))->migrate(MigrateDirection::Up, null, static function (MigrationResult $result) use (&$reported): void {
                $reported[] = $result->metadata->version;
            });
        } catch (\RuntimeException $error) {
            self::assertSame('Migration failed.', $error->getMessage());
        }

        self::assertSame(['1'], $reported);
    }

    #[Test]
    public function shouldExecuteMigrationsUnderLock(): void
    {
        $calls = [];

        $locator = $this->createStub(MigrationsLocatorInterface::class);
        $locator->method('locate')->willReturn([$this->createMetadata('1')]);

        $executor = $this->createStub(MigrationExecutorInterface::class);

        $executor->method('execute')
            ->willReturnCallback(static function (MigrationMetadata $metadata) use (&$calls): MigrationResult {
                $calls[] = 'execute';

                return new MigrationResult($metadata, MigrationExecutedState::Executed, new \DateTimeImmutable(), 0.0, null);
            });

        $migrator = new Migrator($locator, $executor, $this->createLock($calls));

        $migrator->migrate(MigrateDirection::Up, null);
        $migrator->execute(MigrateDirection::Down, '1');

        self::assertSame(['acquire', 'execute', 'release', 'acquire', 'execute', 'release'], $calls);
    }

    #[Test]
    public function shouldReleaseLockOnFailure(): void
    {
        $calls = [];

        $locator = $this->createStub(MigrationsLocatorInterface::class);
        $locator->method('locate')->willReturn([$this->createMetadata('1')]);

        $executor = $this->createStub(MigrationExecutorInterface::class);
        $executor->method('execute')->willThrowException(new \RuntimeException('Migration failed.'));

        $migrator = new Migrator($locator, $executor, $this->createLock($calls));

        try {
            $migrator->migrate(MigrateDirection::Up, null);
        } catch (\RuntimeException $error) {
            self::assertSame('Migration failed.', $error->getMessage());
        }

        self::assertSame(['acquire', 'release'], $calls);
    }

    private function createLock(array &$calls): MigrationLockInterface
    {
        $lock = $this->createStub(MigrationLockInterface::class);

        $lock->method('acquire')->willReturnCallback(static function () use (&$calls): void {
            $calls[] = 'acquire';
        });

        $lock->method('release')->willReturnCallback(static function () use (&$calls): void {
            $calls[] = 'release';
        });

        return $lock;
    }

    private function createMetadata(string $version): MigrationMetadata
    {
        return new MigrationMetadata('default', $version, new \ReflectionClass(Version1::class));
    }
}
