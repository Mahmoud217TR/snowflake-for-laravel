<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;

/**
 * Register compatible unsigned BIGINT primary and foreign Snowflake columns.
 *
 * snowflake() creates a non-auto-incrementing primary key by default;
 * foreignSnowflake() retains Laravel's constrained() and foreign-key helpers.
 */
final class BlueprintMacros
{
    public static function register(): void
    {
        Blueprint::macro(
            'snowflake',
            fn (string $column = 'id'): ColumnDefinition => $this->unsignedBigInteger($column)->primary(),
        );

        Blueprint::macro(
            'foreignSnowflake',
            fn (string $column): ForeignIdColumnDefinition => $this->foreignId($column),
        );
    }
}
