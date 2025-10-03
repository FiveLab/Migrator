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

use FiveLab\Component\Migrator\Locator\FilterVersionsLocator;
use FiveLab\Component\Migrator\Locator\MigrationsLocatorInterface;

readonly class Migrator implements MigratorInterface
{
    public function __construct(
        private MigrationsLocatorInterface $locator,
        private MigrationExecutorInterface $executor
    ) {
    }

    public function migrate(MigrateDirection $direction, ?string $toVersion): iterable
    {
        $locator = $this->locator;

        if ($toVersion) {
            $operator = match ($direction) {
                MigrateDirection::Up   => '<=',
                MigrateDirection::Down => '>=',
            };

            $locator = new FilterVersionsLocator($locator, $toVersion, $operator);
        }

        $results = [];

        foreach ($locator->locate($direction) as $metadata) {
            $results[] = $this->executor->execute($metadata, $direction);
        }

        return $results;
    }
}
