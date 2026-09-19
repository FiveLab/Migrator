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

namespace FiveLab\Component\Migrator;

interface MigratorInterface
{
    /**
     * Migrate to specific version (up/down).
     *
     * @param MigrateDirection                       $direction
     * @param string|null                            $toVersion
     * @param (callable(MigrationResult): void)|null $onResult  Called right after each migration (e.g. for show progress).
     *
     * @return iterable<MigrationResult>
     */
    public function migrate(MigrateDirection $direction, ?string $toVersion, ?callable $onResult = null): iterable;

    /**
     * Execute specific migration (up/down).
     *
     * @param MigrateDirection $direction
     * @param string           $version
     *
     * @return MigrationResult
     */
    public function execute(MigrateDirection $direction, string $version): MigrationResult;
}
