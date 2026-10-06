<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake;

use MahmoudTR\Snowflake\Validation\SnowflakeValidator;
use MahmoudTR\Snowflake\ValueObjects\SnowflakeParts;

/**
 * Application-facing API for generating, inspecting, and validating Snowflakes.
 */
final readonly class Snowflake
{
    public function __construct(
        private SnowflakeGenerator $generator,
        private SnowflakeParser $parser,
        private SnowflakeValidator $validator,
    ) {}

    /**
     * Allocate an ID as a decimal string, safe for JSON and JavaScript clients.
     *
     * @throws Exceptions\SnowflakeException When generation or configuration is unsafe.
     */
    public function generate(): string
    {
        return $this->generator->generate();
    }

    /**
     * Decode an ID using the configured epoch; leading zeroes are accepted.
     *
     * @throws Exceptions\InvalidSnowflake When the ID is not an unsigned decimal within the signed 64-bit range.
     */
    public function inspect(string|int $snowflake): SnowflakeParts
    {
        return $this->parser->parse($snowflake);
    }

    /**
     * Check numeric representation and range, not provenance or database existence.
     */
    public function isValid(mixed $snowflake): bool
    {
        return $this->validator->isValid($snowflake);
    }
}
