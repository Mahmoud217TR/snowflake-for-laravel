<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use MahmoudTR\Snowflake\Casts\AsSnowflake;
use MahmoudTR\Snowflake\Concerns\HasSnowflakeIds;

final class SnowflakeUser extends Model
{
    use HasSnowflakeIds;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['reference_id' => AsSnowflake::class];
    }
}
