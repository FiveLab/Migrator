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

namespace FiveLab\Component\Migrator\Locator;

use FiveLab\Component\Migrator\MigrateDirection;
use FiveLab\Component\Migrator\MigrationMetadata;

interface MigrationsLocatorInterface
{
    /**
     * Locate all possible migrations.
     *
     * @param MigrateDirection $direction
     *
     * @return iterable<MigrationMetadata>
     */
    public function locate(MigrateDirection $direction): iterable;
}
