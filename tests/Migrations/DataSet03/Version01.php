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

namespace FiveLab\Component\Migrator\Tests\Migrations\DataSet03;

use FiveLab\Component\Migrator\Migration\AbstractPdoMigration;

readonly class Version01 extends AbstractPdoMigration
{
    public function getDescription(): string
    {
        return 'pdo version 01';
    }

    protected function doUp(): void
    {
        $this->addSql('SELECT * FROM not_existing_table WHERE label = :foo', [
            'foo' => 'bar',
        ]);
    }

    protected function doDown(): void
    {
        $this->addSql('DROP TABLE not_existing_table');
    }
}
