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

use FiveLab\Component\Migrator\Exception\MigrationIsAbstractException;
use FiveLab\Component\Migrator\Migration\MigrationInterface;

readonly class MigrationMetadata
{
    /**
     * Constructor.
     *
     * @param string                               $group
     * @param string                               $version
     * @param \ReflectionClass<MigrationInterface> $class
     */
    public function __construct(public string $group, public string $version, public \ReflectionClass $class)
    {
    }

    public static function fromPhpFile(string $group, string $filepath): self
    {
        $content = (string) \file_get_contents($filepath);

        /** @var class-string<MigrationInterface> $className */
        $className = self::extractNamespace($filepath, $content).'\\'.self::extractClassName($filepath, $content);

        require_once $filepath;

        $ref = new \ReflectionClass($className);

        if ($ref->isAbstract()) {
            // Abstract class. Ignore.
            throw new MigrationIsAbstractException(\sprintf(
                'The class "%s" is abstract and can\'t be execute.',
                $className
            ));
        }

        if (!$ref->implementsInterface(MigrationInterface::class)) {
            throw new \RuntimeException(\sprintf(
                'The migration class "%s" should implement "%s" interface.',
                $ref->getName(),
                MigrationInterface::class
            ));
        }

        if (!\preg_match('/\\\Version([0-9]+)$/', $ref->getName(), $matches)) {
            throw new \RuntimeException(\sprintf(
                'Invalid migration class "%s". Class name must match App\Migrations\VersionXXXX, where XXXX is a unique version number.',
                $ref->getName()
            ));
        }

        return new self($group, $matches[1], $ref);
    }

    private static function extractNamespace(string $filepath, string $phpContent): string
    {
        if (!\preg_match('/namespace\s+(.+);/', $phpContent, $matches)) {
            throw new \RuntimeException(\sprintf(
                'Can\'t extract namespace from file "%s".',
                $filepath
            ));
        }

        return $matches[1];
    }

    private static function extractClassName(string $filepath, string $phpContent): string
    {
        if (!\preg_match('/class\s+(\S+)/', $phpContent, $matches)) {
            throw new \RuntimeException(\sprintf(
                'Can\'t extract class from file "%s".',
                $filepath
            ));
        }

        return $matches[1];
    }
}
