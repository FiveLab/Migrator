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

namespace FiveLab\Component\Migrator\Tests\Unit;

use FiveLab\Component\Migrator\Exception\MigratorNotFoundException;
use FiveLab\Component\Migrator\MigratorInterface;
use FiveLab\Component\Migrator\MigratorRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class MigratorRegistryTest extends TestCase
{
    private ContainerInterface $container;
    private MigratorRegistry $registry;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->registry = new MigratorRegistry($this->container);
    }

    #[Test]
    public function shouldSuccessGet(): void
    {
        $migrator = $this->createMock(MigratorInterface::class);

        $this->container->expects($this->once())
            ->method('has')
            ->with('bla-bla')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('bla-bla')
            ->willReturn($migrator);

        $result = $this->registry->get('bla-bla');

        self::assertEquals($migrator, $result);
    }

    #[Test]
    public function shouldThrowErrorIfMissed(): void
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with('foo-bar')
            ->willReturn(false);

        $this->container->expects($this->never())
            ->method('get');

        $this->expectException(MigratorNotFoundException::class);
        $this->expectExceptionMessage('The migrator for group "foo-bar" was not found.');

        $this->registry->get('foo-bar');
    }
}
