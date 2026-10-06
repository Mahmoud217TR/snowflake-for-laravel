<?php

namespace MahmoudTR\Snowflake\ValueObjects;

final readonly class GenerationState
{
    public function __construct(
        public int $timestamp,
        public int $sequence,
    ) {}
}
