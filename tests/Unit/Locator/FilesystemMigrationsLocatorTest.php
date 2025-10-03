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
}
