<?php

declare(strict_types=1);

use MahmoudTR\Snowflake\Snowflake;

if (! function_exists('snowflake')) {
    function snowflake(): Snowflake
    {
        return app(Snowflake::class);
    }
}
