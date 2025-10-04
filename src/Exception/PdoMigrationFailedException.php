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

class PdoMigrationFailedException extends \Exception
{
    /**
     * Constructor.
     *
     * @param string                              $sql
     * @param array<string|int, string|int|float> $parameters
     * @param \Throwable                          $previous
     *
     * @throws \JsonException
     */
    public function __construct(
        public readonly string $sql,
        public readonly array  $parameters,
        public \Throwable      $previous
    ) {
        $message = \sprintf(
            'Migration failed - "%s", parameters: %s with message: %s.',
            $this->sql,
            \json_encode($parameters, \JSON_THROW_ON_ERROR),
            \rtrim($this->previous->getMessage(), '.')
        );

        parent::__construct($message, 0, $this->previous);
    }
}
