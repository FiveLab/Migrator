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

namespace FiveLab\Component\Migrator\Tests\Migrations\DataSet06\C;

use FiveLab\Component\Migrator\Migration\AbstractMigration;

readonly class Version01 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'version 01 (C)';
    }

    public function up(): void
    {
    }

    public function down(): void
    {
    }
}
