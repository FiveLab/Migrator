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
use FiveLab\Component\Migrator\Exception\NotMigrationClassException;
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

        /** @var class-string<MigrationInterface>|null $className */
        $className = self::extractClassName($content);

        if (null === $className) {
            // Trait, interface, enum or plain script. Ignore.
            throw new NotMigrationClassException(\sprintf(
                'The file "%s" does not declare a class.',
                $filepath
            ));
        }

        require_once $filepath;

        $ref = new \ReflectionClass($className);

        if ($ref->isAbstract()) {
            // Abstract class. Ignore.
            throw new MigrationIsAbstractException(\sprintf(
                'The class "%s" is abstract and can\'t be execute.',
                $className
            ));
        }

        $isMigration = $ref->implementsInterface(MigrationInterface::class);
        $isVersionName = (bool) \preg_match('/^Version([0-9]+)$/', $ref->getShortName(), $matches);

        if (!$isMigration && !$isVersionName) {
            // Helper class near migrations. Ignore.
            throw new NotMigrationClassException(\sprintf(
                'The class "%s" is not a migration.',
                $className
            ));
        }

        if (!$isMigration) {
            throw new \RuntimeException(\sprintf(
                'The migration class "%s" should implement "%s" interface.',
                $ref->getName(),
                MigrationInterface::class
            ));
        }

        if (!$isVersionName) {
            throw new \RuntimeException(\sprintf(
                'Invalid migration class "%s". Class name must match App\Migrations\VersionXXXX, where XXXX is a unique version number.',
                $ref->getName()
            ));
        }

        return new self($group, $matches[1], $ref);
    }

    private static function extractClassName(string $phpContent): ?string
    {
        $tokens = \PhpToken::tokenize($phpContent);
        $namespace = '';

        foreach ($tokens as $index => $token) {
            if ($token->is(\T_NAMESPACE)) {
                $name = self::findSignificantToken($tokens, $index, 1);
                $namespace = $name?->is([\T_NAME_QUALIFIED, \T_STRING]) ? $name->text : '';

                continue;
            }

            if (!$token->is(\T_CLASS) || self::findSignificantToken($tokens, $index, -1)?->is(\T_DOUBLE_COLON)) {
                continue;
            }

            $name = self::findSignificantToken($tokens, $index, 1);

            if ($name?->is(\T_STRING)) {
                return \ltrim($namespace.'\\'.$name->text, '\\');
            }
        }

        return null;
    }

    /**
     * Find the nearest token which is not a whitespace or a comment.
     *
     * @param array<int, \PhpToken> $tokens
     * @param int                   $index
     * @param int                   $step
     *
     * @return \PhpToken|null
     */
    private static function findSignificantToken(array $tokens, int $index, int $step): ?\PhpToken
    {
        for ($index += $step; isset($tokens[$index]); $index += $step) {
            if (!$tokens[$index]->isIgnorable()) {
                return $tokens[$index];
            }
        }

        return null;
    }
}
