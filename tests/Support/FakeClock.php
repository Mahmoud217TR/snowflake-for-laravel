<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Tests\Support;

use MahmoudTR\Snowflake\Contracts\Clock;

final class FakeClock implements Clock
{
    /** @var list<int> */
    public array $waits = [];

    public function __construct(public int $timestamp) {}

    public function now(): int
    {
        return $this->timestamp;
    }

    public function sleepUntil(int $timestamp): void
    {
        $this->waits[] = $timestamp;
        $this->timestamp = max($this->timestamp, $timestamp);
    }
}
