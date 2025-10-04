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

use FiveLab\Component\Migrator\Console\MigrateCommand;
use FiveLab\Component\Migrator\Exception\MigratorNotFoundException;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;

class MigrateCommandTest extends CommandTestCase
{
    protected ?Command $command;

    protected function setUp(): void
    {
        $this->setUpMySql();

        $this->command = new MigrateCommand($this->createMigratorRegistry());
    }

    protected function tearDown(): void
    {
        $this->dropTables();
    }

    #[Test]
    public function shouldSuccessUp(): void
    {
        $tester = $this->executeCommand(['group' => 'Database']);

        self::assertEquals(0, $tester->getStatusCode());

        $outputLines = \explode(PHP_EOL, $this->getOutputString($tester->getOutput()));

        self::assertCount(4, $outputLines);

        self::assertStringStartsWith('Executed migration FiveLab\Component\Migrator\Tests\Migrations\DataSet02\Version01 in', $outputLines[0]);
        self::assertStringStartsWith('Executed migration FiveLab\Component\Migrator\Tests\Migrations\DataSet02\Version02 in', $outputLines[1]);
        self::assertStringStartsWith('Executed migration FiveLab\Component\Migrator\Tests\Migrations\DataSet02\Version03 in', $outputLines[2]);
        self::assertEquals('', $outputLines[3]);

        $rows = $this->executeSql('SELECT * FROM test_1 ORDER BY id ASC');

        self::assertEquals([
            ['id' => 1, 'label' => 'Bla Bla', 'active' => 1],
            ['id' => 2, 'label' => 'Foo Bar', 'active' => 1],
        ], $rows);
    }

    #[Test]
    public function shouldSuccessUpToSpecificVersion(): void
    {
        $tester = $this->executeCommand([
            'group'   => 'Database',
            'version' => '02',
        ]);

        self::assertEquals(0, $tester->getStatusCode());

        $outputLines = \explode(PHP_EOL, $this->getOutputString($tester->getOutput()));

        self::assertCount(3, $outputLines);

        self::assertStringStartsWith('Executed migration FiveLab\Component\Migrator\Tests\Migrations\DataSet02\Version01 in', $outputLines[0]);
        self::assertStringStartsWith('Executed migration FiveLab\Component\Migrator\Tests\Migrations\DataSet02\Version02 in', $outputLines[1]);
        self::assertEquals('', $outputLines[2]);

        $rows = $this->executeSql('SELECT * FROM test_1 ORDER BY id ASC');

        self::assertEquals([
            ['id' => 1, 'label' => 'Bla Bla'],
            ['id' => 2, 'label' => 'Foo Bar'],
        ], $rows);
    }

    #[Test]
    #[Depends('shouldSuccessUp')]
    public function shouldSuccessDown(): void
    {
        $this->executeCommand(['group' => 'Database']);
        $tester = $this->executeCommand(['group' => 'Database', '--down' => 1]);

        $tables = $this->getTableNames();

        self::assertEquals(0, $tester->getStatusCode());
        self::assertEquals(['migration_versions'], $tables);

        $outputLines = \explode(PHP_EOL, $this->getOutputString($tester->getOutput()));

        self::assertCount(4, $outputLines);

        self::assertStringStartsWith('Executed migration FiveLab\Component\Migrator\Tests\Migrations\DataSet02\Version03 in', $outputLines[0]);
        self::assertStringStartsWith('Executed migration FiveLab\Component\Migrator\Tests\Migrations\DataSet02\Version02 in', $outputLines[1]);
        self::assertStringStartsWith('Executed migration FiveLab\Component\Migrator\Tests\Migrations\DataSet02\Version01 in', $outputLines[2]);
        self::assertEquals('', $outputLines[3]);
    }

    #[Test]
    #[Depends('shouldSuccessUp')]
    public function shouldSuccessDownToSpecificVersion(): void
    {
        $this->executeCommand(['group' => 'Database']);
        $tester = $this->executeCommand(['group' => 'Database', 'version' => '02', '--down' => 1]);

        self::assertEquals(0, $tester->getStatusCode());

        $outputLines = \explode(PHP_EOL, $this->getOutputString($tester->getOutput()));

        self::assertCount(3, $outputLines);

        self::assertStringStartsWith('Executed migration FiveLab\Component\Migrator\Tests\Migrations\DataSet02\Version03 in', $outputLines[0]);
        self::assertStringStartsWith('Executed migration FiveLab\Component\Migrator\Tests\Migrations\DataSet02\Version02 in', $outputLines[1]);
        self::assertEquals('', $outputLines[2]);

        $rows = $this->executeSql('SELECT * FROM test_1 ORDER BY id ASC');

        self::assertEquals([
            ['id' => 1, 'label' => 'Bla Bla'],
            ['id' => 2, 'label' => 'Foo Bar'],
        ], $rows);
    }

    #[Test]
    public function shouldFailIfGroupNotFound(): void
    {
        $this->expectException(MigratorNotFoundException::class);
        $this->expectExceptionMessage('The migrator for group "Foo" was not found.');

        $this->executeCommand(['group' => 'Foo']);
    }

    #[Test]
    public function shouldFailInInteractiveMode(): void
    {
        $tester = $this->executeCommand(['group' => 'database'], true);

        self::assertEquals(1, $tester->getStatusCode());

        $output = $this->getOutputString($tester->getOutput());

        $expectedOutput = <<<OUTPUT
 WARNING! You are about to run a database migrations (Up). Are you sure you wish to continue? (yes/no) [no]:
 > 

 [ERROR] Migration canceled.
OUTPUT;

        self::assertEquals(\trim($expectedOutput), \trim($output));
    }
}
