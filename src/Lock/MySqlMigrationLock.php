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

namespace FiveLab\Component\Migrator\Lock;

/**
 * The lock based on MySQL named locks (GET_LOCK). The lock is held by the connection,
 * so MySQL releases it automatically if the process dies.
 */
readonly class MySqlMigrationLock implements MigrationLockInterface
{
    public function __construct(
        private \PDO   $pdo,
        private string $name = 'fivelab_migrator',
        private int    $timeout = 60
    ) {
    }

    public function acquire(): void
    {
        $stmt = $this->pdo->prepare('SELECT GET_LOCK(?, ?)');

        if (false === $stmt || false === $stmt->execute([$this->name, $this->timeout]) || 1 !== (int) $stmt->fetchColumn()) {
            throw new \RuntimeException(\sprintf(
                'Can\'t acquire the migration lock "%s" in %d seconds. Probably another process is executing migrations.',
                $this->name,
                $this->timeout
            ));
        }
    }

    public function release(): void
    {
        $stmt = $this->pdo->prepare('SELECT RELEASE_LOCK(?)');

        if (false !== $stmt) {
            $stmt->execute([$this->name]);
        }
    }
}
