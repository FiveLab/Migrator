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

namespace FiveLab\Component\Migrator\Tests;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

readonly class ServiceContainer implements ContainerInterface
{
    public function __construct(private array $services)
    {
    }

    public function get(string $id): object
    {
        return $this->services[$id] ?? throw new class() extends \InvalidArgumentException implements NotFoundExceptionInterface { // phpcs:ignore
        };
    }

    public function has(string $id): bool
    {
        return \array_key_exists($id, $this->services);
    }
}
