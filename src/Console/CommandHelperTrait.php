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
use FiveLab\Component\Migrator\MigrationResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

trait CommandHelperTrait
{
    protected function configureMigrateInput(Command $command, bool $requireVersion = false): void
    {
        $command
            ->addArgument('group', InputArgument::REQUIRED, 'The group in which to run migrations. ')
            ->addArgument('version', $requireVersion ? InputArgument::REQUIRED : InputArgument::OPTIONAL, 'The version to run migrations.')
            ->addOption('down', null, InputOption::VALUE_NONE, 'Down migrations.');
    }

    /**
     * Read input data
     *
     * @param InputInterface $input
     *
     * @return array{"0": string, "1": string, "2": MigrateDirection}
     */
    protected function readInput(InputInterface $input): array
    {
        return [
            $input->getArgument('group'),
            $input->getArgument('version'),
            $input->getOption('down') ? MigrateDirection::Down : MigrateDirection::Up,
        ];
    }

    protected function confirmExecuteMigration(InputInterface $input, OutputInterface $output, string $question): bool
    {
        $style = new SymfonyStyle($input, $output);

        if ($input->isInteractive() && !$style->confirm($question, false)) {
            $style->error('Migration canceled.');

            return false;
        }

        return true;
    }

    protected function outputMigrationResult(OutputInterface $output, MigrationResult $result): void
    {
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
}
