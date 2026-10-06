<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Clock;

use MahmoudTR\Snowflake\Contracts\Clock;

final class SystemClock implements Clock
{
    public function now(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    public function sleepUntil(int $timestamp): void
    {
        while (true) {
            $remaining = $timestamp - $this->now();

            if ($remaining <= 0) {
                return;
            }

            usleep($remaining * 1000);
        }
    }
}
