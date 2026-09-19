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

namespace FiveLab\Component\Migrator\Tests\Unit\Locator;

use FiveLab\Component\Migrator\Locator\FilesystemMigrationsLocator;
use FiveLab\Component\Migrator\MigrateDirection;
use FiveLab\Component\Migrator\MigrationMetadata;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class FilesystemMigrationsLocatorTest extends TestCase
{
    private FilesystemMigrationsLocator $locator;

    protected function setUp(): void
    {
        $this->locator = new FilesystemMigrationsLocator(__DIR__.'/../../Migrations/DataSet01', 'Bla');
    }

    #[Test]
    #[TestWith([MigrateDirection::Up, ['01', '02', '03']])]
    #[TestWith([MigrateDirection::Down, ['03', '02', '01']])]
    public function shouldSuccessLocate(MigrateDirection $direction, array $expected): void
    {
        $generator = $this->locator->locate($direction);
        $migrations = \iterator_to_array($generator);

        $result = \array_map(static fn(MigrationMetadata $m) => $m->version, $migrations);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function shouldFailOnDuplicateVersions(): void
    {
        $locator = new FilesystemMigrationsLocator(__DIR__.'/../../Migrations/DataSet06', 'Bla');

        try {
            \iterator_to_array($locator->locate(MigrateDirection::Up));
        } catch (\RuntimeException $error) {
            $message = $error->getMessage();

            self::assertStringStartsWith('The migrations in group "Bla" have duplicate versions: "1" (', $message);
            self::assertStringContainsString('DataSet06\A\Version1 in ', $message);
            self::assertStringContainsString('DataSet06\B\Version1 in ', $message);
            self::assertStringContainsString('DataSet06\C\Version01 in ', $message);
            self::assertStringNotContainsString('Version2', $message);

            return;
        }

        self::fail('The duplicate versions were not detected.');
    }

    #[Test]
    #[TestWith([MigrateDirection::Up, ['1', '9', '10']])]
    #[TestWith([MigrateDirection::Down, ['10', '9', '1']])]
    public function shouldLocateInVersionOrderRegardlessOfPaths(MigrateDirection $direction, array $expected): void
    {
        $locator = new FilesystemMigrationsLocator(__DIR__.'/../../Migrations/DataSet05', 'Bla');

        $migrations = \iterator_to_array($locator->locate($direction));

        $result = \array_map(static fn(MigrationMetadata $m) => $m->version, $migrations);

        self::assertEquals($expected, $result);
    }
}
