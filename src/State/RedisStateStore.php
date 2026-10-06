<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\State;

use MahmoudTR\Snowflake\Contracts\StateStore;
use MahmoudTR\Snowflake\Redis\LuaScript;
use MahmoudTR\Snowflake\ValueObjects\GenerationState;
use RuntimeException;

final class RedisStateStore implements StateStore
{
    public function __construct(
        private readonly LuaScript $nextStateScript,
        private readonly string $prefix = 'snowflake:state',
    ) {}

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
