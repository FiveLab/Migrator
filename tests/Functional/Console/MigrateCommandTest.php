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
use FiveLab\Component\Migrator\Locator\FilesystemMigrationsLocator;
use FiveLab\Component\Migrator\Migrator;
use FiveLab\Component\Migrator\MigratorRegistry;
use FiveLab\Component\Migrator\Tests\Functional\MySqlTestTrait;
use FiveLab\Component\Migrator\Tests\ServiceContainer;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateCommandTest extends TestCase
{
    private MigrateCommand $command;

    use MySqlTestTrait {
        setUp as protected setUpMySql;
    }

    protected function setUp(): void
    {
        $this->setUpMySql();

        $locator = new FilesystemMigrationsLocator(__DIR__.'/../../Migrations/DataSet02', 'Database');

        $migrator = new Migrator($locator, $this->executor);
        $registry = new MigratorRegistry(new ServiceContainer(['Database' => $migrator]));
        $this->command = new MigrateCommand($registry);
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
        self::assertEquals('', $this->getOutputString($tester->getOutput()));

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
        self::assertEquals('', $this->getOutputString($tester->getOutput()));

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
        self::assertEquals('', $this->getOutputString($tester->getOutput()));
    }

    #[Test]
    #[Depends('shouldSuccessUp')]
    public function shouldSuccessDownToSpecificVersion(): void
    {
        $this->executeCommand(['group' => 'Database']);
        $tester = $this->executeCommand(['group' => 'Database', 'version' => '02', '--down' => 1]);

        self::assertEquals(0, $tester->getStatusCode());
        self::assertEquals('', $this->getOutputString($tester->getOutput()));

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

    private function executeCommand(array $args): CommandTester
    {
        $tester = new CommandTester($this->command);

        $tester->execute($args);

        return $tester;
    }

    private function getOutputString(OutputInterface $output): string
    {
        if (!$output instanceof StreamOutput) {
            throw new \InvalidArgumentException(\sprintf(
                'Only StreamOutput supported for get output, but "%s" given.',
                \get_class($output)
            ));
        }

        $stream = $output->getStream();

        \rewind($stream);

        return \stream_get_contents($stream);
    }
}
