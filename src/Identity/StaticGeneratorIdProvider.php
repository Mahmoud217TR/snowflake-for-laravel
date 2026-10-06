<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Identity;

use MahmoudTR\Snowflake\Contracts\GeneratorIdProvider;

/**
 * Return a configured generator ID without allocating or discovering an identity.
 */
final class StaticGeneratorIdProvider implements GeneratorIdProvider
{
    public function __construct(
        private readonly int $generatorId
    ) {}

    public function id(): int
    {
        return $this->generatorId;
    }
}
