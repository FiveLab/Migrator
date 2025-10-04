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

use FiveLab\Component\Migrator\MigrateDirection;
use FiveLab\Component\Migrator\MigrationExecutedState;
use FiveLab\Component\Migrator\MigratorRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'migrations:migrate', description: 'Run migrations.')]
class MigrateCommand extends Command
{
    public function __construct(private readonly MigratorRegistry $registry)
    {
        parent::__construct(null);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('group', InputArgument::REQUIRED, 'The group in which to run migrations. ')
            ->addArgument('version', InputArgument::OPTIONAL, 'The version to run migrations.')
            ->addOption('down', null, InputOption::VALUE_NONE, 'Down migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $group = $input->getArgument('group');
        $toVersion = $input->getArgument('version');
        $direction = $input->getOption('down') ? MigrateDirection::Down : MigrateDirection::Up;

        $question = sprintf(
            '<comment>WARNING!</comment> You are about to execute a migration <comment>%s</comment> (<comment>%s</comment>). Are you sure you wish to continue?',
            $group,
            $direction->name
        );

        if ($input->isInteractive() && !$style->confirm($question, false)) {
            $style->error('Migration canceled.');

            return self::FAILURE;
        }

        $migrator = $this->registry->get($group);

        foreach ($migrator->migrate($direction, $toVersion) as $result) {
            if ($result->state === MigrationExecutedState::Skipped) {
                $output->writeln(\sprintf(
                    'Skip migration <comment>%s</comment>.',
                    $result->metadata->class->name
                ), OutputInterface::VERBOSITY_DEBUG);
            } else {
                $output->writeln(\sprintf(
                    'Executed migration <comment>%s</comment> in %.2f seconds.',
                    $result->metadata->class->name,
                    $result->executeTime
                ), OutputInterface::VERBOSITY_NORMAL);
            }
        }

        return self::SUCCESS;
    }
}
