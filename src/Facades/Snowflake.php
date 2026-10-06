<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Facades;

use Illuminate\Support\Facades\Facade;
use MahmoudTR\Snowflake\ValueObjects\SnowflakeParts;

/**
 * @method static string generate()
 * @method static bool isValid(mixed $snowflake)
 * @method static SnowflakeParts inspect(string|int $snowflake)
 *
 * @see \MahmoudTR\Snowflake\Snowflake
 */
final class Snowflake extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \MahmoudTR\Snowflake\Snowflake::class;
    }
}
