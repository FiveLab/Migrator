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

use ClickHouseDB\Client;
use FiveLab\Component\Migrator\Factory\ClickHouseMigrationFactory;
use FiveLab\Component\Migrator\MigrationMetadata;
use FiveLab\Component\Migrator\Tests\Migrations\DataSet04\Version01;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ClickHouseMigrationFactoryTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
    }

    #[Test]
    public function shouldSuccessCreate(): void
    {
        $factory = new ClickHouseMigrationFactory($this->client);
        $metadata = MigrationMetadata::fromPhpFile('clickhouse', __DIR__.'/../../Migrations/DataSet04/Version01.php');
        $migration = $factory->create($metadata);

        self::assertInstanceOf(Version01::class, $migration);

        $refClient = new \ReflectionProperty($migration, 'client');
        $client = $refClient->getValue($migration);

        self::assertEquals($this->client, $client);
    }
}
