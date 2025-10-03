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

namespace FiveLab\Component\Migrator\Tests\Unit\DependencyInjection;

use FiveLab\Component\Migrator\Console\MigrateCommand;
use FiveLab\Component\Migrator\DependencyInjection\MigratorExtension;
use FiveLab\Component\Migrator\Locator\FilesystemMigrationsLocator;
use FiveLab\Component\Migrator\MigrationExecutor;
use FiveLab\Component\Migrator\Migrator;
use FiveLab\Component\Migrator\MigratorRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Reference;

class MigratorExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new MigratorExtension(),
        ];
    }

    #[Test]
    public function shouldSuccessLoad(): void
    {
        $this->load([
            'migrations' => [
                'default' => [
                    'group'   => 'database',
                    'path'    => __DIR__,
                    'factory' => 'migrator.factory',
                    'history' => 'migrator.history',
                ],

                'clickhouse' => [
                    'path'    => \realpath(__DIR__.'/../'),
                    'factory' => 'migrator.factory.clickhouse',
                    'history' => 'migrator.history',
                ],
            ],
        ]);

        // Check registry
        $this->assertService('migrations.migrator_registry', MigratorRegistry::class, [
            new ServiceLocatorArgument([
                'database'   => new Reference('migrations.migrator.default'),
                'clickhouse' => new Reference('migrations.migrator.clickhouse'),
            ]),
        ]);

        // Check locators
        $this->assertService('migrations.migrator.default.locator', FilesystemMigrationsLocator::class, [
            __DIR__,
            'database',
        ]);

        $this->assertService('migrations.migrator.clickhouse.locator', FilesystemMigrationsLocator::class, [
            \realpath(__DIR__.'/../'),
            'clickhouse',
        ]);

        // Check executors
        $this->assertService('migrations.migrator.default.executor', MigrationExecutor::class, [
            new Reference('migrator.history'),
            new Reference('migrator.factory'),
        ]);

        $this->assertService('migrations.migrator.clickhouse.executor', MigrationExecutor::class, [
            new Reference('migrator.history'),
            new Reference('migrator.factory.clickhouse'),
        ]);

        // Check migrators
        $this->assertService('migrations.migrator.default', Migrator::class, [
            new Reference('migrations.migrator.default.locator'),
            new Reference('migrations.migrator.default.executor'),
        ]);

        $this->assertService('migrations.migrator.clickhouse', Migrator::class, [
            new Reference('migrations.migrator.clickhouse.locator'),
            new Reference('migrations.migrator.clickhouse.executor'),
        ]);

        // Check console commands
        $this->assertService('migrations.console.migrate', MigrateCommand::class, [
            new Reference('migrations.migrator_registry'),
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithTag('migrations.console.migrate', 'console.command');
    }

    private function assertService(string $id, string $expectedClass, array $arguments): void
    {
        $this->assertContainerBuilderHasService($id, $expectedClass);
        $serviceArguments = $this->container->getDefinition($id)->getArguments();

        self::assertCount(\count($arguments), $serviceArguments, \sprintf(
            'Mismatch count arguments for service "%s". Expected %d arguments, but pass %d.',
            $id,
            \count($arguments),
            \count($serviceArguments)
        ));

        foreach ($arguments as $index => $argument) {
            $this->assertContainerBuilderHasServiceDefinitionWithArgument($id, $index, $argument);
        }
    }
}
