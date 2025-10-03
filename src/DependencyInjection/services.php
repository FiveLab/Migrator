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

use FiveLab\Component\Migrator\Console\MigrateCommand;
use FiveLab\Component\Migrator\MigratorRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->alias(MigratorRegistry::class, 'migrations.migrator_registry')
        ->set('migrations.migrator_registry', MigratorRegistry::class)
            ->args([
                '', // Service locator
            ])

        ->set('migrations.console.migrate', MigrateCommand::class)
            ->args([
                service('migrations.migrator_registry'),
            ])
            ->tag('console.command');
};
