<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake;

use Carbon\CarbonImmutable;
use MahmoudTR\Snowflake\Configuration\SnowflakeConfig;
use MahmoudTR\Snowflake\Exceptions\InvalidSnowflake;
use MahmoudTR\Snowflake\ValueObjects\SnowflakeParts;

final class SnowflakeParser
{
    private const GENERATOR_BITS = 10;

    private const SEQUENCE_BITS = 12;

    private const MAX_SNOWFLAKE = '9223372036854775807';

    private const GENERATOR_MASK = (1 << self::GENERATOR_BITS) - 1;

    private const SEQUENCE_MASK = (1 << self::SEQUENCE_BITS) - 1;

    private const TIMESTAMP_SHIFT = self::GENERATOR_BITS + self::SEQUENCE_BITS;

    public function __construct(
        private readonly SnowflakeConfig $config,
    ) {}

    public function parse(string|int $snowflake): SnowflakeParts
    {
        $id = $this->normalize($snowflake);

        $sequence = $id & self::SEQUENCE_MASK;

        $generatorId = ($id >> self::SEQUENCE_BITS) & self::GENERATOR_MASK;

        $timestamp = ($id >> self::TIMESTAMP_SHIFT) + $this->config->epoch;

        return new SnowflakeParts(
            timestamp: CarbonImmutable::createFromTimestampMsUTC($timestamp),
            generatorId: $generatorId,
            sequence: $sequence,
        );
    }

    private function normalize(string|int $snowflake): int
    {
        if (PHP_INT_SIZE < 8) {
            throw new InvalidSnowflake(
                'Snowflake parsing requires a 64-bit PHP runtime.',
            );
        }

        if (is_int($snowflake)) {
            if ($snowflake < 0) {
                throw new InvalidSnowflake(
                    'Snowflake ID must be a non-negative integer.',
                );
            }

            return $snowflake;
        }

        if ($snowflake === '' || ! ctype_digit($snowflake)) {
            throw new InvalidSnowflake(
                'Snowflake ID must contain only decimal digits.',
            );
        }

        $normalized = ltrim($snowflake, '0');

        if ($normalized === '') {
            $normalized = '0';
        }

        if ($this->exceedsMaximum($normalized)) {
            throw new InvalidSnowflake(
                'Snowflake ID exceeds the signed 64-bit integer range.',
            );
        }

        return (int) $normalized;
    }

    private function exceedsMaximum(string $snowflake): bool
    {
        $maximumLength = strlen(self::MAX_SNOWFLAKE);
        $length = strlen($snowflake);

        return $length > $maximumLength
            || (
                $length === $maximumLength
                && strcmp($snowflake, self::MAX_SNOWFLAKE) > 0
            );
    }
}
