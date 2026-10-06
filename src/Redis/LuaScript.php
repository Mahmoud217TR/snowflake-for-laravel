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
        return $this->redis->eval(
            $this->script,
            // @phpstan-ignore argument.type (Laravel normalizes EVAL arguments; the native Redis mixin signature is incorrect here.)
            count($keys),
            ...$keys,
            ...$arguments,
        );
    }
}
