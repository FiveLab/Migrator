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

namespace FiveLab\Component\Migrator\Factory;

use FiveLab\Component\Migrator\Migration\MigrationInterface;
use FiveLab\Component\Migrator\MigrationMetadata;

readonly class PdoMigrationFactory implements MigrationFactoryInterface
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function create(MigrationMetadata $metadata): MigrationInterface
    {
        return $metadata->class->newInstance($this->pdo);
    }
}
