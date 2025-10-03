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

readonly class MigrationResult
{
    public function __construct(
        public MigrationMetadata      $metadata,
        public MigrationExecutedState $state,
        public \DateTimeInterface     $executedAt,
        public float                  $executeTime,
        public ?string                $description
    ) {
    }
}
