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

use FiveLab\Component\Migrator\Factory\PdoMigrationFactory;
use FiveLab\Component\Migrator\Migration\AbstractPdoMigration;
use FiveLab\Component\Migrator\MigrationMetadata;
use FiveLab\Component\Migrator\Tests\Migrations\DataSet02\Version01;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PdoMigrationFactoryTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(\PDO::class);
    }

    #[Test]
    public function shouldSuccessCreate(): void
    {
        $factory = new PdoMigrationFactory($this->pdo);
        $metadata = MigrationMetadata::fromPhpFile('Default', __DIR__.'/../../Migrations/DataSet02/Version01.php');
        $migration = $factory->create($metadata);

        self::assertInstanceOf(Version01::class, $migration);

        $refPdo = new \ReflectionProperty(AbstractPdoMigration::class, 'pdo');

        self::assertEquals($this->pdo, $refPdo->getValue($migration));
    }
}
