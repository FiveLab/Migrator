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

namespace FiveLab\Component\Migrator\History;

use FiveLab\Component\Migrator\MigrationMetadata;
use FiveLab\Component\Migrator\MigrationResult;

interface MigrationsHistoryInterface
{
    /**
     * Is migration executed previously?
     *
     * @param MigrationMetadata $metadata
     *
     * @return bool
     */
    public function isExecuted(MigrationMetadata $metadata): bool;

    /**
     * Get migration result based on metadata from history.
     *
     * @param MigrationMetadata $metadata
     *
     * @return MigrationResult
     */
    public function get(MigrationMetadata $metadata): MigrationResult;

    /**
     * Add executed migration to history.
     *
     * @param MigrationResult $result
     */
    public function add(MigrationResult $result): void;

    /**
     * Delete executed migration from history.
     *
     * @param MigrationMetadata $metadata
     */
    public function delete(MigrationMetadata $metadata): void;
}
