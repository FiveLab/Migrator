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

namespace FiveLab\Component\Migrator\Tests\Migrations\DataSet02;

use FiveLab\Component\Migrator\Migration\AbstractPdoMigration;

readonly class Version01 extends AbstractPdoMigration
{
    public function getDescription(): string
    {
        return 'pdo version 01';
    }

    protected function doUp(): void
    {
        $this->addSql('CREATE TABLE test_1 (
            id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
            label VARCHAR(255) NOT NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    protected function doDown(): void
    {
        $this->addSql('DROP TABLE test_1');
    }
}
