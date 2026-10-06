<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Exceptions;

final class InvalidGeneratorId extends SnowflakeException
{
    public function __construct(
        public readonly int $generatorId,
        public readonly int $maximum,
    ) {
        parent::__construct(sprintf(
            'Generator ID must be between 0 and %d, %d given.',
            $maximum,
            $generatorId,
        ));
    }
}
