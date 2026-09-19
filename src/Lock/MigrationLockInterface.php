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
 * The lock prevents parallel execution of migrations (e.g. several instances of the application on deploy).
 */
interface MigrationLockInterface
{
    /**
     * Acquire the lock, waiting while another process holds it.
     *
     * @throws \RuntimeException If the lock can't be acquired.
     */
    public function acquire(): void;

    /**
     * Release the lock.
     */
    public function release(): void;
}
