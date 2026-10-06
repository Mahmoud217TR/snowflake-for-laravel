<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use MahmoudTR\Snowflake\Validation\SnowflakeValidator;

final class Snowflake implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        if (! app(SnowflakeValidator::class)->isValid($value)) {
            $fail('The :attribute must be a valid Snowflake ID.');
        }
    }
}
