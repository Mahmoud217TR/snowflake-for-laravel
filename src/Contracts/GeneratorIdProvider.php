<?php

namespace MahmoudTR\Snowflake\Contracts;

interface GeneratorIdProvider
{
    public function id(): int;
}
