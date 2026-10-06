<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Exceptions;

/**
 * Generation was attempted before the configured Snowflake epoch.
 */
final class EpochNotReached extends SnowflakeException
{
    /**
     * @param  int  $timestamp  Current absolute Unix timestamp in milliseconds.
     * @param  int  $epoch  Configured absolute Unix timestamp in milliseconds.
     */
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
