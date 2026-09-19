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

use FiveLab\Component\Migrator\Exception\MigrationIsAbstractException;
use FiveLab\Component\Migrator\MigrateDirection;
use FiveLab\Component\Migrator\MigrationMetadata;

readonly class FilesystemMigrationsLocator implements MigrationsLocatorInterface
{
    public function __construct(private string $directory, private string $group)
    {
    }

    public function locate(MigrateDirection $direction): iterable
    {
        $iterator = new \RecursiveDirectoryIterator($this->directory);
        $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);
        $iterator = new \RegexIterator($iterator, '/^.+\.php$/');

        $pathnames = [];

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $pathnames[] = $file->getPathname();
        }

        // Sort paths first to keep a stable order for migrations with equal versions.
        \sort($pathnames, \SORT_NATURAL);

        $migrations = [];

        foreach ($pathnames as $pathname) {
            try {
                $migrations[] = MigrationMetadata::fromPhpFile($this->group, $pathname);
            } catch (MigrationIsAbstractException) {
                continue;
            }
        }

        \usort($migrations, static fn(MigrationMetadata $a, MigrationMetadata $b): int => \strnatcmp($a->version, $b->version));

        if (MigrateDirection::Down === $direction) {
            $migrations = \array_reverse($migrations);
        }

        yield from $migrations;
    }
}
