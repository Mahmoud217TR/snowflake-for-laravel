<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Contracts;

use MahmoudTR\Snowflake\ValueObjects\GenerationState;

/**
 * Allocate per-generator timestamp/sequence pairs; shared stores must do so atomically.
 */
interface StateStore
{
    /**
     * Start at sequence zero for a new timestamp, or increment at the same timestamp.
     * Older timestamps return the previous state unchanged. Sequence values may
     * exceed the layout limit; the generator handles exhaustion by waiting.
     *
     * @param  int  $timestamp  Absolute Unix timestamp in milliseconds.
     */
    public function next(int $generatorId, int $timestamp): GenerationState;
}
