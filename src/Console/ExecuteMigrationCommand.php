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

namespace FiveLab\Component\Migrator\Console;

use FiveLab\Component\Migrator\MigratorRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrations:execute', description: 'Execute specific migration by version.')]
class ExecuteMigrationCommand extends Command
{
    use CommandHelperTrait;

    public function __construct(private readonly MigratorRegistry $registry)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->configureMigrateInput($this, true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        [$group, $version, $direction] = $this->readInput($input);

        $question = \sprintf(
            '<comment>WARNING!</comment> You are about to execute a <comment>%s</comment> migration by version <comment>%s</comment> (<comment>%s</comment>). Are you sure you wish to continue?',
            $group,
            $version,
            $direction->name
        );

        if (!$this->confirmExecuteMigration($input, $output, $question)) {
            return self::FAILURE;
        }

        $migrator = $this->registry->get($group);

        $result = $migrator->execute($direction, $version);

        $this->outputMigrationResult($output, $result);

        return self::SUCCESS;
    }
}
