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

namespace FiveLab\Component\Migrator\Migration;

use FiveLab\Component\Migrator\Exception\MigrationFailedException;

abstract readonly class AbstractPdoMigration extends AbstractMigration
{
    /**
     * @var \ArrayIterator<int, array{"0": "string", "1": array<string, mixed>, "2": string}>
     */
    private \ArrayIterator $entries;

    public function __construct(private \PDO $pdo)
    {
        $this->entries = new \ArrayIterator();
    }

    final public function up(): void
    {
        $this->doUp();

        foreach ($this->entries as $entry) {
            $this->executeEntry($entry);
        }
    }

    final public function down(): void
    {
        $this->doDown();

        foreach ($this->entries as $entry) {
            $this->executeEntry($entry);
        }
    }

    abstract protected function doUp(): void;

    abstract protected function doDown(): void;

    /**
     * Add SQL
     *
     * @param string                              $sql
     * @param array<string|int, int|float|string|null> $parameters
     */
    final protected function addSql(string $sql, array $parameters = []): void
    {
        $this->entries->offsetSet(\count($this->entries), [$sql, $parameters, \get_class($this)]);
    }

    /**
     * Execute SQL entry.
     *
     * @param array{"0": string, "1": array<string|int, string|int|float|null>, "2": string} $entry
     *
     * @throws MigrationFailedException
     */
    private function executeEntry(array $entry): void
    {
        $stmt = $this->pdo->prepare($entry[0]);

        try {
            $stmt->execute($entry[1]);
        } catch (\Throwable $error) {
            $message = \sprintf(
                '"%s", parameters: %s with message: %s.',
                $entry[0],
                \json_encode($entry[1], \JSON_THROW_ON_ERROR),
                \rtrim($error->getMessage(), '.')
            );

            throw new MigrationFailedException($entry[2], $message, 0, $error);
        }
    }
}
