<?php

declare(strict_types=1);

use MahmoudTR\Snowflake\Snowflake;

if (! function_exists('snowflake')) {
    /**
     * Resolve the application-facing API; generation uses the shared state store.
     */
    function snowflake(): Snowflake
    {
        return app(Snowflake::class);
    }
}
