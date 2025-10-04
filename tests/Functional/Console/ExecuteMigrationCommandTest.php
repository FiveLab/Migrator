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

namespace FiveLab\Component\Migrator\Tests\Functional\Console;

use FiveLab\Component\Migrator\Console\ExecuteMigrationCommand;
use FiveLab\Component\Migrator\MigrateDirection;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;

class ExecuteMigrationCommandTest extends CommandTestCase
{
    protected ?Command $command;

    protected function setUp(): void
    {
        $this->setUpMySql();

        $this->command = new ExecuteMigrationCommand($this->createMigratorRegistry());
    }

    protected function tearDown(): void
    {
        $this->dropTables();
    }

    #[Test]
    public function shouldSuccessExecuteUp(): void
    {
        $tester = $this->executeCommand([
            'group'   => 'Database',
            'version' => '01',
        ]);

        self::assertEquals(0, $tester->getStatusCode());

        $rows = $this->executeSql('SELECT id, label FROM test_1');

        self::assertCount(0, $rows);
    }

    #[Test]
    public function shouldSuccessExecuteDown(): void
    {
        $this->createMigratorRegistry()->get('Database')->migrate(MigrateDirection::Up, null);

        $tester = $this->executeCommand([
            'group'   => 'Database',
            'version' => '03',
            '--down'  => true,
        ]);

        self::assertEquals(0, $tester->getStatusCode());

        $rows = $this->executeSql('SELECT * FROM test_1 ORDER BY id ASC');

        self::assertEquals([
            ['id' => 1, 'label' => 'Bla Bla'],
            ['id' => 2, 'label' => 'Foo Bar'],
        ], $rows);
    }
}
