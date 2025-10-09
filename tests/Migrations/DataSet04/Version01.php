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

namespace FiveLab\Component\Migrator\Tests\Migrations\DataSet04;

use ClickHouseDB\Client;
use FiveLab\Component\Migrator\Migration\AbstractMigration;

readonly class Version01 extends AbstractMigration
{
    public function __construct(private Client $client)
    {
    }

    public function getDescription(): string
    {
        return 'pdo version 01';
    }

    public function up(): void
    {
        $this->client->write('CREATE TABLE test_1 (
            id UInt32,
            created_at DATETIME,
            label String
        ) ENGINE=MergeTree ORDER BY (created_at)');
    }

    public function down(): void
    {
        $this->client->write('DROP TABLE test_1');
    }
}
