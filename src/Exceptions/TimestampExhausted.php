<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Exceptions;

/**
 * The configured epoch's 41-bit timestamp lifetime has been exhausted.
 */
final class TimestampExhausted extends SnowflakeException
{
    /**
     * @param  int  $timestamp  Milliseconds since the configured epoch, not Unix time.
     * @param  int  $maximum  Maximum supported epoch-relative milliseconds.
     */
    public function __construct(
        public readonly int $timestamp,
        public readonly int $maximum,
    ) {
        parent::__construct(sprintf(
            'Snowflake timestamp %d exceeds the maximum supported value %d.',
            $timestamp,
            $maximum,
        ));
    }
}
