<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUniqueStringIds;
use MahmoudTR\Snowflake\Casts\AsSnowflake;
use MahmoudTR\Snowflake\SnowflakeGenerator;
use MahmoudTR\Snowflake\Validation\SnowflakeValidator;

/**
 * Generate non-incrementing model keys, expose them as strings, and validate route IDs.
 *
 * The primary key is automatically cast with AsSnowflake; other numeric ID
 * attributes need their own casts when they should be exposed as strings.
 */
trait HasSnowflakeIds
{
    use HasUniqueStringIds;

    /**
     * Merge new casts with existing casts on the model.
     *
     * @param  array  $casts
     * @return $this
     */
    abstract public function mergeCasts($casts);

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    abstract public function getKeyName();

    public function initializeHasSnowflakeIds(): void
    {
        $this->mergeCasts([$this->getKeyName() => AsSnowflake::class]);
    }

    public function newUniqueId(): string
    {
        return app(SnowflakeGenerator::class)->generate();
    }

    /**
     * Fill missing IDs without replacing an explicitly assigned zero.
     */
    public function setUniqueIds(): void
    {
        foreach ($this->uniqueIds() as $column) {
            // Zero is a valid Snowflake, not an empty identifier.
            if ($this->{$column} === null || $this->{$column} === '') {
                $this->{$column} = $this->newUniqueId();
            }
        }
    }

    protected function isValidUniqueId(mixed $value): bool
    {
        return app(SnowflakeValidator::class)->isValid($value);
    }
}
