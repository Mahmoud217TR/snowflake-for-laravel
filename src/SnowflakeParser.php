<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake;

use Carbon\CarbonImmutable;
use MahmoudTR\Snowflake\Configuration\SnowflakeConfig;
use MahmoudTR\Snowflake\Exceptions\InvalidSnowflake;
use MahmoudTR\Snowflake\ValueObjects\SnowflakeParts;

final class SnowflakeParser
{
    public function __construct(
        private readonly SnowflakeConfig $config,
    ) {}

    public function parse(string|int $snowflake): SnowflakeParts
    {
        $id = $this->normalize($snowflake);

        $sequence = $id & SnowflakeLayout::MAX_SEQUENCE;

        $generatorId = ($id >> SnowflakeLayout::SEQUENCE_BITS) & SnowflakeLayout::MAX_GENERATOR_ID;

        $timestamp = ($id >> SnowflakeLayout::TIMESTAMP_SHIFT) + $this->config->epoch;

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
        $maximumLength = strlen(SnowflakeLayout::MAX_SNOWFLAKE);
        $length = strlen($snowflake);

        return $length > $maximumLength
            || (
                $length === $maximumLength
                && strcmp($snowflake, SnowflakeLayout::MAX_SNOWFLAKE) > 0
            );
    }
}
