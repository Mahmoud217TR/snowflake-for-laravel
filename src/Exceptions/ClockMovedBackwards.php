<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Exceptions;

/**
 * The clock is behind stored allocation history by more than the allowed tolerance.
 */
final class ClockMovedBackwards extends SnowflakeException
{
    /**
     * @param  int  $currentTimestamp  Current absolute Unix timestamp in milliseconds.
     * @param  int  $previousTimestamp  Stored absolute Unix timestamp in milliseconds.
     */
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
