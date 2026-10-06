<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Exceptions;

final class SequenceExhausted extends SnowflakeException
{
    public function __construct(
        public readonly int $timestamp,
        public readonly int $maximum,
    ) {
        parent::__construct(sprintf(
            'Snowflake sequence exhausted at timestamp %d after %d IDs.',
            $timestamp,
            $maximum + 1,
        ));
    }
}
