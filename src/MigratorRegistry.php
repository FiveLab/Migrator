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

use FiveLab\Component\Migrator\Exception\MigratorNotFoundException;
use Psr\Container\ContainerInterface;

readonly class MigratorRegistry
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function get(string $group): MigratorInterface
    {
        if ($this->container->has($group)) {
            return $this->container->get($group);
        }

        throw new MigratorNotFoundException(\sprintf(
            'The migrator for group "%s" was not found.',
            $group
        ));
    }
}
