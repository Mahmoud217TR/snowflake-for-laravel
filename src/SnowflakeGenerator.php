<?php

namespace MahmoudTR\Snowflake;

use MahmoudTR\Snowflake\Configuration\SnowflakeConfig;
use MahmoudTR\Snowflake\Contracts\Clock;
use MahmoudTR\Snowflake\Contracts\GeneratorIdProvider;
use MahmoudTR\Snowflake\Contracts\StateStore;
use MahmoudTR\Snowflake\Exceptions\ClockMovedBackwards;
use MahmoudTR\Snowflake\Exceptions\EpochNotReached;
use MahmoudTR\Snowflake\Exceptions\InvalidGeneratorId;
use MahmoudTR\Snowflake\Exceptions\TimestampExhausted;
use RuntimeException;

final class SnowflakeGenerator
{
    private const TIMESTAMP_BITS = 41;
    private const GENERATOR_BITS = 10;
    private const SEQUENCE_BITS = 12;

    private const MAX_GENERATOR_ID = (1 << self::GENERATOR_BITS) - 1;
    private const MAX_SEQUENCE = (1 << self::SEQUENCE_BITS) - 1;
    private const MAX_TIMESTAMP = (1 << self::TIMESTAMP_BITS) - 1;

    private const TIMESTAMP_SHIFT =
        self::GENERATOR_BITS + self::SEQUENCE_BITS;

    public function __construct(
        private readonly Clock $clock,
        private readonly GeneratorIdProvider $generatorIdProvider,
        private readonly StateStore $stateStore,
        private readonly SnowflakeConfig $config,
    ) {
        if (PHP_INT_SIZE < 8) {
            throw new RuntimeException(
                'Snowflake requires a 64-bit PHP runtime.',
            );
        }
    }

    public function generate(): string
    {
        $generatorId = $this->generatorIdProvider->id();

        $this->validateGeneratorId($generatorId);

        while (true) {
            $now = $this->clock->now();

            $state = $this->stateStore->next(
                generatorId: $generatorId,
                timestamp: $now,
            );

            /*
             * The StateStore returns the previous state unchanged when
             * the system clock has moved backwards.
             */
            if ($state->timestamp > $now) {
                $rollback = $state->timestamp - $now;

                if ($rollback > $this->config->maxRollbackMs) {
                    throw new ClockMovedBackwards(
                        currentTimestamp: $now,
                        previousTimestamp: $state->timestamp,
                    );
                }

                $this->clock->sleepUntil($state->timestamp);

                continue;
            }

            /*
             * All 4096 sequence values for this millisecond have already
             * been consumed. Wait for the next millisecond and retry.
             */
            if ($state->sequence > self::MAX_SEQUENCE) {
                $this->clock->sleepUntil($state->timestamp + 1);

                continue;
            }

            $timestamp = $this->validateTimestamp($state->timestamp);

            return $this->composeId(
                timestamp: $timestamp,
                generatorId: $generatorId,
                sequence: $state->sequence,
            );
        }
    }

    private function validateGeneratorId(int $generatorId): void
    {
        if (
            $generatorId < 0
            || $generatorId > self::MAX_GENERATOR_ID
        ) {
            throw new InvalidGeneratorId(
                generatorId: $generatorId,
                maximum: self::MAX_GENERATOR_ID,
            );
        }
    }

    private function validateTimestamp(int $timestamp): int
    {
        if ($timestamp < $this->config->epoch) {
            throw new EpochNotReached(
                timestamp: $timestamp,
                epoch: $this->config->epoch,
            );
        }

        $relativeTimestamp = $timestamp - $this->config->epoch;

        if ($relativeTimestamp > self::MAX_TIMESTAMP) {
            throw new TimestampExhausted(
                timestamp: $relativeTimestamp,
                maximum: self::MAX_TIMESTAMP,
            );
        }

        return $relativeTimestamp;
    }

    private function composeId(
        int $timestamp,
        int $generatorId,
        int $sequence,
    ): string {
        return (string) (
            ($timestamp << self::TIMESTAMP_SHIFT)
            | ($generatorId << self::SEQUENCE_BITS)
            | $sequence
        );
    }
}
