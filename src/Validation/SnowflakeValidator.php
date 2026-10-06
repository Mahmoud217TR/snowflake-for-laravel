<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Validation;

use MahmoudTR\Snowflake\Exceptions\InvalidSnowflake;
use MahmoudTR\Snowflake\SnowflakeParser;

/**
 * Validate numeric representation and range without asserting provenance or existence.
 */
final readonly class SnowflakeValidator
{
    public function __construct(
        private SnowflakeParser $parser,
    ) {}

    /**
     * Accept only parseable strings and integers; invalid IDs return false.
     */
    public function isValid(mixed $value): bool
    {
        if (! is_string($value) && ! is_int($value)) {
            return false;
        }

        try {
            $this->parser->parse($value);

            return true;
        } catch (InvalidSnowflake) {
            return false;
        }
    }
}
