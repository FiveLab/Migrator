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
use FiveLab\Component\Migrator\Exception\NotMigrationClassException;
use FiveLab\Component\Migrator\MigrateDirection;
use FiveLab\Component\Migrator\MigrationMetadata;

readonly class FilesystemMigrationsLocator implements MigrationsLocatorInterface
{
    public function __construct(private string $directory, private string $group)
    {
    }

    public function locate(MigrateDirection $direction): iterable
    {
        if (!\is_dir($this->directory)) {
            throw new \RuntimeException(\sprintf(
                'The migrations directory "%s" does not exist.',
                $this->directory
            ));
        }

        $iterator = new \RecursiveDirectoryIterator($this->directory);
        $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);
        $iterator = new \RegexIterator($iterator, '/^.+\.php$/');

        $pathnames = [];

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $pathnames[] = $file->getPathname();
        }

        // Sort paths for a stable loading order of files, independent of the filesystem.
        \sort($pathnames, \SORT_NATURAL);

        $migrations = [];

        foreach ($pathnames as $pathname) {
            try {
                $migrations[] = MigrationMetadata::fromPhpFile($this->group, $pathname);
            } catch (MigrationIsAbstractException | NotMigrationClassException) {
                continue;
            }
        }

        \usort($migrations, static fn(MigrationMetadata $a, MigrationMetadata $b): int => \strnatcmp($a->version, $b->version));

        $this->assertUniqueVersions($migrations);

        if (MigrateDirection::Down === $direction) {
            $migrations = \array_reverse($migrations);
        }

        yield from $migrations;
    }

    /**
     * Check that versions are unique, otherwise the history can't distinguish the migrations.
     *
     * @param array<int, MigrationMetadata> $migrations Migrations sorted by version.
     */
    private function assertUniqueVersions(array $migrations): void
    {
        $duplicates = [];

        foreach ($migrations as $index => $metadata) {
            $previous = $migrations[$index - 1] ?? null;

            if ($previous && 0 === \strnatcmp($previous->version, $metadata->version)) {
                $duplicates[$previous->version][$previous->class->getName()] = $previous->class->getFileName();
                $duplicates[$previous->version][$metadata->class->getName()] = $metadata->class->getFileName();
            }
        }

        if (!\count($duplicates)) {
            return;
        }

        $messages = [];

        foreach ($duplicates as $version => $classes) {
            $entries = [];

            foreach ($classes as $className => $fileName) {
                $entries[] = \sprintf('%s in %s', $className, $fileName);
            }

            $messages[] = \sprintf('"%s" (%s)', $version, \implode(', ', $entries));
        }

        throw new \RuntimeException(\sprintf(
            'The migrations in group "%s" have duplicate versions: %s.',
            $this->group,
            \implode('; ', $messages)
        ));
    }
}
