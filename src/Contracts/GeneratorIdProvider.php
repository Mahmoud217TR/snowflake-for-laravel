<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Contracts;

/**
 * Identify the generator whose timestamp and sequence state should be used.
 */
interface GeneratorIdProvider
{
    /**
     * Return a stable ID in 0–1023; the generator validates the range.
     */
    public function id(): int;
}
