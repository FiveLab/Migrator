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

namespace FiveLab\Component\Migrator\Exception;

class MigrationFailedException extends \Exception
{
    public function __construct(public readonly string $fqcn, string $message, int $code = 0, ?\Throwable $previous = null)
    {
        $message = \sprintf(
            'Migration failed (%s) - %s.',
            $this->fqcn,
            \rtrim($message, '.')
        );

        parent::__construct($message, $code, $previous);
    }
}
