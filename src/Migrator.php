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

use FiveLab\Component\Migrator\Lock\MigrationLockInterface;
use FiveLab\Component\Migrator\Locator\FilterVersionsLocator;
use FiveLab\Component\Migrator\Locator\MigrationsLocatorInterface;

readonly class Migrator implements MigratorInterface
{
    public function __construct(
        private MigrationsLocatorInterface $locator,
        private MigrationExecutorInterface $executor,
        private ?MigrationLockInterface    $lock = null
    ) {
    }

    public function migrate(MigrateDirection $direction, ?string $toVersion): iterable
    {
        $locator = $this->locator;

        if (null !== $toVersion) {
            $operator = match ($direction) {
                MigrateDirection::Up   => '<=',
                MigrateDirection::Down => '>=',
            };

            $locator = new FilterVersionsLocator($locator, $toVersion, $operator);
        }

        $this->lock?->acquire();

        try {
            $results = [];

            foreach ($locator->locate($direction) as $metadata) {
                $results[] = $this->executor->execute($metadata, $direction);
            }

            return $results;
        } finally {
            $this->lock?->release();
        }
    }

    public function execute(MigrateDirection $direction, string $version): MigrationResult
    {
        $locator = $this->locator;
        $locator = new FilterVersionsLocator($locator, $version, '=');

        $versions = \iterator_to_array($locator->locate($direction));

        if (!\count($versions)) {
            throw new \RuntimeException(\sprintf(
                'The version "%s" does not exists.',
                $version
            ));
        }

        $this->lock?->acquire();

        try {
            return $this->executor->execute($versions[0], $direction);
        } finally {
            $this->lock?->release();
        }
    }
}
