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

use FiveLab\Component\Migrator\MigrationExecutedState;
use FiveLab\Component\Migrator\MigrationResult;
use FiveLab\Component\Migrator\MigratorRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrations:migrate', description: 'Run migrations.')]
class MigrateCommand extends Command
{
    use CommandHelperTrait;

    public function __construct(private readonly MigratorRegistry $registry)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->configureMigrateInput($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        [$group, $toVersion, $direction] = $this->readInput($input);

        $question = \sprintf(
            '<comment>WARNING!</comment> You are about to run a <comment>%s</comment> migrations (<comment>%s</comment>). Are you sure you wish to continue?',
            $group,
            $direction->name
        );

        if (!$this->confirmExecuteMigration($input, $output, $question)) {
            return self::FAILURE;
        }

        $migrator = $this->registry->get($group);
        $executed = 0;

        $migrator->migrate($direction, $toVersion, function (MigrationResult $result) use ($output, &$executed): void {
            $this->outputMigrationResult($output, $result);

            if (MigrationExecutedState::Executed === $result->state) {
                $executed++;
            }
        });

        if (!$executed) {
            $output->writeln('No migrations to execute.');
        }

        return self::SUCCESS;
    }
}
