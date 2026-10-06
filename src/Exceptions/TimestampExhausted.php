<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Exceptions;

final class TimestampExhausted extends SnowflakeException
{
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
