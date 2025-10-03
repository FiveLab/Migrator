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

namespace FiveLab\Component\Migrator\Tests\Unit\Factory;

use FiveLab\Component\Migrations\Tests\DataSet01\Version01;
use FiveLab\Component\Migrator\Factory\NativeMigrationFactory;
use FiveLab\Component\Migrator\Migration\MigrationInterface;
use FiveLab\Component\Migrator\MigrationMetadata;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class NativeMigrationFactoryTest extends TestCase
{
    private NativeMigrationFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new NativeMigrationFactory();
    }

    #[Test]
    public function shouldSuccessCreate(): void
    {
        $metadata = MigrationMetadata::fromPhpFile('Default', __DIR__.'/../../Migrations/DataSet01/01/Version01.php');
        $migration = $this->factory->create($metadata);

        self::assertInstanceOf(MigrationInterface::class, $migration);
        self::assertInstanceOf(Version01::class, $migration);
    }
}
