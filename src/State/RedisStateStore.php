<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\State;

use MahmoudTR\Snowflake\Contracts\StateStore;
use MahmoudTR\Snowflake\Redis\LuaScript;
use MahmoudTR\Snowflake\ValueObjects\GenerationState;
use RuntimeException;

/**
 * Atomically coordinate per-generator allocations through a Redis Lua script.
 *
 * Sharing producers must use the same Redis database and effective key prefix.
 * State keys have no expiry; deleting or evicting them can permit duplicate IDs.
 */
final class RedisStateStore implements StateStore
{
    public function __construct(
        private readonly LuaScript $nextStateScript,
        private readonly string $prefix = 'snowflake:state',
    ) {}

    /**
     * @throws RuntimeException When the Lua script returns an unexpected response shape.
     */
    public function next(int $generatorId, int $timestamp): GenerationState
    {
        $result = $this->nextStateScript->execute(
            keys: [$this->key($generatorId)],
            arguments: [$timestamp],
        );

        if (
            ! is_array($result)
            || count($result) !== 2
        ) {
            throw new RuntimeException(
                'Unexpected response from Snowflake Redis state script.',
            );
        }

        return new GenerationState(
            timestamp: (int) $result[0],
            sequence: (int) $result[1],
        );
    }

    private function key(int $generatorId): string
    {
        return "{$this->prefix}:{$generatorId}";
    }
}
