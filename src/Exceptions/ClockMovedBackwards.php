<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Exceptions;

final class ClockMovedBackwards extends SnowflakeException
{
    public function __construct(
        public readonly int $currentTimestamp,
        public readonly int $previousTimestamp,
    ) {
        parent::__construct(sprintf(
            'Clock moved backwards by %d milliseconds.',
            $previousTimestamp - $currentTimestamp,
        ));
    }
}
