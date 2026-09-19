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

namespace FiveLab\Component\Migrator\Tests\Migrations\DataSet09;

use FiveLab\Component\Migrator\Migration\AbstractMigration;

readonly class Version2 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'version 2';
    }

    public function up(): void
    {
        throw new \RuntimeException('Something went wrong.');
    }

    public function down(): void
    {
    }
}
