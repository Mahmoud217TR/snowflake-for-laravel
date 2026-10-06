<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Contracts;

interface Clock
{
    public function now(): int;

    public function sleepUntil(int $timestamp): void;
}
