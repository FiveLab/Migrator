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

readonly class Version03 extends AbstractPdoMigration
{
    public function getDescription(): string
    {
        return 'pdo version 01';
    }

    protected function doUp(): void
    {
        $this->addSql('ALTER TABLE test_1 ADD COLUMN active TINYINT(1) DEFAULT NULL');
        $this->addSql('UPDATE test_1 SET active = 1');
        $this->addSql('ALTER TABLE test_1 MODIFY COLUMN active TINYINT(1) NOT NULL');
    }

    protected function doDown(): void
    {
        $this->addSql('ALTER TABLE test_1 DROP COLUMN active');
    }
}
