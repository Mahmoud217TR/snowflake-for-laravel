<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Epoch
    |--------------------------------------------------------------------------
    |
    | Non-negative Unix timestamp in milliseconds. The default is
    | January 1, 2026, 00:00:00 UTC, with about 69.7 years of timestamp space.
    | Choose it before generating IDs; never change it for an existing ID
    | namespace. All producers and readers must use the same epoch.
    |
    */

    'epoch' => (int) env('SNOWFLAKE_EPOCH', 1767225600000),

    /*
    |--------------------------------------------------------------------------
    | Generator ID
    |--------------------------------------------------------------------------
    |
    | Required integer from 0 through 1023; zero is valid. There is no
    | automatic allocation. Producers without shared state must use distinct
    | IDs. Processes may share an ID when they share the same Redis state.
    |
    */

    'generator_id' => env('SNOWFLAKE_GENERATOR_ID'),

    /*
    |--------------------------------------------------------------------------
    | Clock rollback
    |--------------------------------------------------------------------------
    |
    | Non-negative milliseconds. Rollback within this tolerance waits and
    | retries; larger rollback throws ClockMovedBackwards. Zero disables
    | rollback tolerance. Sequence exhaustion always waits for the next
    | millisecond and does not use this tolerance.
    |
    */

    'max_rollback_ms' => (int) env('SNOWFLAKE_MAX_ROLLBACK_MS', 5),

    /*
    |--------------------------------------------------------------------------
    | Generation state
    |--------------------------------------------------------------------------
    |
    | Redis coordinates sequence allocation atomically across processes.
    | Local state is in-memory only: it is not shared across requests,
    | processes, or restarts. Prefer Redis for shared application workloads.
    |
    | Keep Redis state durable and protected from eviction or deletion;
    | losing allocation history can permit duplicate IDs. Redis errors
    | propagate; there is no automatic fallback to local state.
    |
    */

    'state' => [
        // Supported drivers: "redis" and "local".
        'driver' => env(
            'SNOWFLAKE_STATE_DRIVER',
            'redis',
        ),

        // Named Laravel Redis connection from config/database.php; Redis only.
        'connection' => env(
            'SNOWFLAKE_REDIS_CONNECTION',
            'default',
        ),

        // Keys use "{prefix}:{generatorId}", plus Laravel's Redis prefix.
        // Coordinating processes must share the same database and effective prefix.
        'prefix' => env(
            'SNOWFLAKE_REDIS_PREFIX',
            'snowflake:state',
        ),
    ],

];
