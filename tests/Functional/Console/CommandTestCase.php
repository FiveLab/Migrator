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

use FiveLab\Component\Migrator\Tests\Functional\MySqlTestTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends TestCase
{
    protected ?Command $command = null;

    use MySqlTestTrait {
        setUp as protected setUpMySql;
    }

    protected function executeCommand(array $args, bool $interactive = false): CommandTester
    {
        if (!$this->command) {
            throw new \RuntimeException('The command not initialized. Please inject you command to $command property.');
        }

        $tester = new CommandTester($this->command);

        $tester->execute($args, [
            'interactive' => $interactive,
        ]);

        return $tester;
    }

    protected function getOutputString(OutputInterface $output): string
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
