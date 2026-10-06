<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake;

final class SnowflakeLayout
{
    public const TIMESTAMP_BITS = 41;

    public const GENERATOR_BITS = 10;

    public const SEQUENCE_BITS = 12;

    public const MAX_TIMESTAMP = (1 << self::TIMESTAMP_BITS) - 1;

    public const MAX_GENERATOR_ID = (1 << self::GENERATOR_BITS) - 1;

    public const MAX_SEQUENCE = (1 << self::SEQUENCE_BITS) - 1;

    public const MAX_SNOWFLAKE = '9223372036854775807';

    public const TIMESTAMP_SHIFT = self::GENERATOR_BITS + self::SEQUENCE_BITS;
}
