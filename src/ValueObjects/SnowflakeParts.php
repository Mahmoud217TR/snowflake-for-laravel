<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\ValueObjects;

use Carbon\CarbonImmutable;

/**
 * Decoded Snowflake fields, with an absolute UTC timestamp including milliseconds.
 */
final readonly class SnowflakeParts
{
    public function __construct(
        public CarbonImmutable $timestamp,
        public int $generatorId,
        public int $sequence,
    ) {}
}
