<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Contracts;

/**
 * Wall-clock time source used for generation and retry waits.
 */
interface Clock
{
    /**
     * Return the absolute Unix timestamp in milliseconds, not an epoch-relative value.
     */
    public function now(): int;

    /**
     * Wait until the clock reaches the given absolute Unix millisecond timestamp.
     */
    public function sleepUntil(int $timestamp): void;
}
