<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Configuration;

use InvalidArgumentException;

/**
 * Immutable epoch and tolerated wall-clock rollback, both measured in milliseconds.
 */
final readonly class SnowflakeConfig
{
    /**
     * @param  int  $epoch  Non-negative absolute Unix timestamp in milliseconds.
     * @param  int  $maxRollbackMs  Non-negative rollback tolerance; zero rejects all rollback.
     *
     * @throws InvalidArgumentException When either value is negative.
     */
    public function __construct(
        public int $epoch,
        public int $maxRollbackMs = 5,
    ) {
        if ($this->epoch < 0) {
            throw new InvalidArgumentException(
                'Snowflake epoch must be a non-negative Unix timestamp in milliseconds.',
            );
        }

        if ($this->maxRollbackMs < 0) {
            throw new InvalidArgumentException(
                'Maximum clock rollback must be zero or greater.',
            );
        }
    }
}
