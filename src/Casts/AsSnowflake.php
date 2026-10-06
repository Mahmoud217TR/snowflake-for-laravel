<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use MahmoudTR\Snowflake\Exceptions\InvalidSnowflake;
use MahmoudTR\Snowflake\Validation\SnowflakeValidator;

/**
 * Expose nullable BIGINT attributes as strings and validate assigned Snowflake IDs.
 */
final class AsSnowflake implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null
            ? null
            : (string) $value;
    }

    /**
     * Preserve null; accept valid integer or decimal-string IDs without float coercion.
     *
     * @throws InvalidSnowflake When a non-null assignment is not a valid ID.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! app(SnowflakeValidator::class)->isValid($value)) {
            throw new InvalidSnowflake(
                "The [{$key}] attribute must contain a valid Snowflake ID.",
            );
        }

        return (string) $value;
    }
}
