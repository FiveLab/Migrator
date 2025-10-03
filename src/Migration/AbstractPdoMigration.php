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

abstract readonly class AbstractPdoMigration extends AbstractMigration
{
    /**
     * @var \ArrayIterator<int, array{"0": "string", "1": array<string, mixed>}>
     */
    private \ArrayIterator $entries;

    public function __construct(private \PDO $pdo)
    {
        $this->entries = new \ArrayIterator();
    }

    final public function up(): void
    {
        $this->doUp();

        foreach ($this->entries as [$sql, $parameters]) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($parameters);
        }
    }

    final public function down(): void
    {
        $this->doDown();

        foreach ($this->entries as [$sql, $parameters]) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($parameters);
        }
    }

    abstract protected function doUp(): void;

    abstract protected function doDown(): void;

    /**
     * Add SQL
     *
     * @param string                              $sql
     * @param array<string|int, int|float|string> $parameters
     */
    final protected function addSql(string $sql, array $parameters = []): void
    {
        $this->entries->offsetSet(\count($this->entries), [$sql, $parameters]);
    }
}
