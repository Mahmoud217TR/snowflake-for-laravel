<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake;

use MahmoudTR\Snowflake\Validation\SnowflakeValidator;
use MahmoudTR\Snowflake\ValueObjects\SnowflakeParts;

final readonly class Snowflake
{
    public function __construct(
        private SnowflakeGenerator $generator,
        private SnowflakeParser $parser,
        private SnowflakeValidator $validator,
    ) {}

    public function generate(): string
    {
        return $this->generator->generate();
    }

    public function inspect(string|int $snowflake): SnowflakeParts
    {
        return $this->parser->parse($snowflake);
    }

    public function isValid(mixed $snowflake): bool
    {
        return $this->validator->isValid($snowflake);
    }
}
