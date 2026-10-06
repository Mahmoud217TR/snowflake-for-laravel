<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;

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
