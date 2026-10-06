<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Identity;

use MahmoudTR\Snowflake\Contracts\GeneratorIdProvider;
use MahmoudTR\Snowflake\Exceptions\InvalidGeneratorId;
use MahmoudTR\Snowflake\SnowflakeLayout;

/**
 * Return a configured generator ID without allocating or discovering an identity.
 */
final class StaticGeneratorIdProvider implements GeneratorIdProvider
{
    /**
     * @throws InvalidGeneratorId When the ID is outside the fixed layout's range.
     */
    public function __construct(
        private readonly int $generatorId
    ) {
        if ($generatorId < 0 || $generatorId > SnowflakeLayout::MAX_GENERATOR_ID) {
            throw new InvalidGeneratorId($generatorId, SnowflakeLayout::MAX_GENERATOR_ID);
        }
    }

    public function id(): int
    {
        return $this->generatorId;
    }
}
