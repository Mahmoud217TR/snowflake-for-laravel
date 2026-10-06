<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\ValueObjects;

/**
 * An allocation snapshot; sequence may exceed 4095 to signal exhaustion.
 */
final readonly class GenerationState
{
    /**
     * @param  int  $timestamp  Absolute Unix timestamp in milliseconds, before epoch subtraction.
     */
    public function __construct(
        public int $timestamp,
        public int $sequence,
    ) {}
}
