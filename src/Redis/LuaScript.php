<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Redis;

use Illuminate\Redis\Connections\Connection;

/**
 * Execute Lua source through Laravel's normalized PhpRedis/Predis EVAL API.
 */
final class LuaScript
{
    public function __construct(
        private readonly Connection $redis,
        private readonly string $script,
    ) {}

    /**
     * Pass positional key names as KEYS and arguments as ARGV; Redis errors propagate.
     */
    public function execute(
        array $keys = [],
        array $arguments = [],
    ): mixed {
        return $this->redis->eval(
            $this->script,
            // @phpstan-ignore argument.type (Laravel normalizes EVAL arguments; the native Redis mixin signature is incorrect here.)
            count($keys),
            ...$keys,
            ...$arguments,
        );
    }
}
