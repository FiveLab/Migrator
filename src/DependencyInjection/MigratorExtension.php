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

namespace FiveLab\Component\Migrator\DependencyInjection;

use FiveLab\Component\Migrator\Locator\FilesystemMigrationsLocator;
use FiveLab\Component\Migrator\MigrationExecutor;
use FiveLab\Component\Migrator\Migrator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class MigratorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!\count($config['migrations'])) {
            return;
        }

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__));
        $loader->load('services.php');

        $migrators = [];

        foreach ($config['migrations'] as $name => $migratorConfig) {
            $migratorServiceId = \sprintf('migrations.migrator.%s', $name);
            $locatorServiceId = \sprintf('%s.locator', $migratorServiceId);
            $executorServiceId = \sprintf('%s.executor', $migratorServiceId);

            $locatorServiceDef = new Definition(FilesystemMigrationsLocator::class)
                ->setArguments([
                    $migratorConfig['path'],
                    $migratorConfig['group'],
                ]);

            $executorServiceDef = new Definition(MigrationExecutor::class)
                ->setArguments([
                    new Reference($migratorConfig['history']),
                    new Reference($migratorConfig['factory']),
                ]);

            $migratorServiceDef = new Definition(Migrator::class)
                ->setArguments([
                    new Reference($locatorServiceId),
                    new Reference($executorServiceId),
                ]);

            $container->addDefinitions([
                $locatorServiceId  => $locatorServiceDef,
                $executorServiceId => $executorServiceDef,
                $migratorServiceId => $migratorServiceDef,
            ]);

            $migrators[$migratorConfig['group']] = new Reference($migratorServiceId);
        }

        $container->getDefinition('migrations.migrator_registry')
            ->replaceArgument(0, new ServiceLocatorArgument($migrators));
    }

    public function getAlias(): string
    {
        return 'fivelab_migrator';
    }
}
