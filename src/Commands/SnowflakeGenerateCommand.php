<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Commands;

use Illuminate\Console\Command;
use MahmoudTR\Snowflake\SnowflakeGenerator;

/**
 * Print one generated decimal ID; generation failures propagate to Artisan.
 */
final class SnowflakeGenerateCommand extends Command
{
    protected $signature = 'snowflake:generate';

    protected $description = 'Generate a Snowflake ID';

    public function handle(SnowflakeGenerator $generator): int
    {
        $this->line($generator->generate());

        return self::SUCCESS;
    }
}
