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

readonly class Version02 extends AbstractPdoMigration
{
    public function getDescription(): string
    {
        return 'pdo version 01';
    }

    protected function doUp(): void
    {
        $this->addSql('INSERT INTO test_1 (id, label) VALUES (?, ?)', [1, 'Bla Bla']);
        $this->addSql('INSERT INTO test_1 (id, label, description) VALUES (:id, :label, :description)', ['id' => 2, 'label' => 'Foo Bar', 'description' => 'some description']);
        $this->addSql('INSERT INTO test_1 (id, label, description) VALUES (:id, :label, :description)', ['id' => 3, 'label' => 'Bar Bar', 'description' => null]);
    }

    protected function doDown(): void
    {
    }
}
