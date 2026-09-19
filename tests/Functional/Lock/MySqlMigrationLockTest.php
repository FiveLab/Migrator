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

namespace FiveLab\Component\Migrator\Tests\Functional\Lock;

use FiveLab\Component\Migrator\Lock\MySqlMigrationLock;
use FiveLab\Component\Migrator\Tests\Functional\MySqlTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MySqlMigrationLockTest extends TestCase
{
    use MySqlTestTrait;

    #[Test]
    public function shouldPreventAcquireFromAnotherConnection(): void
    {
        $first = new MySqlMigrationLock($this->pdo, 'migrator_test', 0);
        $second = new MySqlMigrationLock(new \PDO(\getenv('MYSQL_DSN'), \getenv('MYSQL_USER'), \getenv('MYSQL_PASSWORD')), 'migrator_test', 0);

        $first->acquire();

        try {
            $second->acquire();

            self::fail('The lock was acquired twice.');
        } catch (\RuntimeException $error) {
            self::assertStringStartsWith('Can\'t acquire the migration lock "migrator_test" in 0 seconds.', $error->getMessage());
        } finally {
            $first->release();
        }

        $second->acquire();
        $second->release();
    }
}
