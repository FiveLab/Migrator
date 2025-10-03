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
use FiveLab\Component\Migrator\Locator\FilterVersionsLocator;
use FiveLab\Component\Migrator\MigrateDirection;
use FiveLab\Component\Migrator\MigrationMetadata;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class FilterVersionsLocatorTest extends TestCase
{
    private FilesystemMigrationsLocator $fsLocator;

    protected function setUp(): void
    {
        $this->fsLocator = new FilesystemMigrationsLocator(__DIR__.'/../../Migrations/DataSet01', 'Bla');
    }

    #[Test]
    #[TestWith([MigrateDirection::Up, '03', '<=', ['01', '02', '03']])]
    #[TestWith([MigrateDirection::Down, '01', '>=', ['03', '02', '01']])]
    #[TestWith([MigrateDirection::Up, '02', '<=', ['01', '02']])]
    #[TestWith([MigrateDirection::Down, '02', '>=', ['03', '02']])]
    #[TestWith([MigrateDirection::Up, '02', '=', ['02']])]
    #[TestWith([MigrateDirection::Down, '02', '=', ['02']])]
    public function shouldSuccessFilter(MigrateDirection $direction, string $toVersion, string $mode, array $expected): void
    {
        $locator = new FilterVersionsLocator($this->fsLocator, $toVersion, $mode);

        $generator = $locator->locate($direction);
        $migrations = \iterator_to_array($generator);

        $result = \array_map(static fn(MigrationMetadata $m) => $m->version, $migrations);

        self::assertEquals($expected, $result);
    }
}
