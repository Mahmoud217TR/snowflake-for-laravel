<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Configuration;

use InvalidArgumentException;

final readonly class SnowflakeConfig
{
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
