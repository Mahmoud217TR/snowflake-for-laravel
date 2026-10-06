<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Contracts;

interface GeneratorIdProvider
{
    public function id(): int;
}
