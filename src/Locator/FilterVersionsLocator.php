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

readonly class FilterVersionsLocator implements MigrationsLocatorInterface
{
    public function __construct(
        private MigrationsLocatorInterface $locator,
        private string                     $version,
        private string                     $mode
    ) {
    }

    public function locate(MigrateDirection $direction): iterable
    {
        $generator = $this->locator->locate($direction);

        foreach ($generator as $metadata) {
            $accepted = match ($this->mode) {
                '='     => \strcmp($metadata->version, $this->version) === 0,
                '>='    => \strcmp($metadata->version, $this->version) >= 0,
                '<='    => \strcmp($metadata->version, $this->version) <= 0,
                default => throw new \InvalidArgumentException(\sprintf(
                    'Invalid compare mode "%s". Possible only "=", ">=" and "<=".',
                    $this->mode
                ))
            };

            if ($accepted) {
                yield $metadata;
            }
        }
    }
}
