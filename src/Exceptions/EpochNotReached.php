<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Exceptions;

final class EpochNotReached extends SnowflakeException
{
    public function __construct(
        public readonly int $timestamp,
        public readonly int $epoch,
    ) {
        parent::__construct(sprintf(
            'Current timestamp %d is before the configured Snowflake epoch %d.',
            $timestamp,
            $epoch,
        ));
    }
}
