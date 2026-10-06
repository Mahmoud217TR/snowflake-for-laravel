<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Contracts;

use MahmoudTR\Snowflake\ValueObjects\GenerationState;

interface StateStore
{
    public function next(int $generatorId, int $timestamp): GenerationState;
}
