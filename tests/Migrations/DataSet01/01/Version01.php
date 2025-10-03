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

namespace FiveLab\Component\Migrations\Tests\DataSet01; // phpcs:ignore

use FiveLab\Component\Migrator\Tests\Migrations\DataSet01\AbstractDataSetMigration;

readonly class Version01 extends AbstractDataSetMigration
{
    public function getDescription(): string
    {
        return 'version 01';
    }

    public function up(): void
    {
    }

    public function down(): void
    {
    }
}
