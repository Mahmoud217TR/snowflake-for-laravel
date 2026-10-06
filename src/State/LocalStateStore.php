<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\State;

use MahmoudTR\Snowflake\Contracts\StateStore;
use MahmoudTR\Snowflake\ValueObjects\GenerationState;

/**
 * Keep allocation history in this instance only, isolated by generator ID.
 *
 * State is neither persistent nor shared across requests, processes, or restarts.
 */
final class LocalStateStore implements StateStore
{
    /** @var array<int, GenerationState> */
    private array $states = [];

    public function next(int $generatorId, int $timestamp): GenerationState
    {
        $state = $this->states[$generatorId] ?? null;

        if ($state === null || $timestamp > $state->timestamp) {
            return $this->states[$generatorId] = new GenerationState(
                timestamp: $timestamp,
                sequence: 0,
            );
        }

        if ($timestamp < $state->timestamp) {
            return $state;
        }

        return $this->states[$generatorId] = new GenerationState(
            timestamp: $timestamp,
            sequence: $state->sequence + 1,
        );
    }
}
