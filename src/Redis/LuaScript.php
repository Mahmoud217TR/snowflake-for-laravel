<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Redis;

use Illuminate\Redis\Connections\Connection;

final class LuaScript
{
    public function __construct(
        private readonly Connection $redis,
        private readonly string $script,
    ) {}

    public function execute(
        array $keys = [],
        array $arguments = [],
    ): mixed {
        return $this->redis->evalsha(
            $this->script,
            count($keys),
            ...$keys,
            ...$arguments,
        );
    }
}
