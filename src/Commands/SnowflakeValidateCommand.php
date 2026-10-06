<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Commands;

use Illuminate\Console\Command;
use MahmoudTR\Snowflake\Validation\SnowflakeValidator;

/**
 * Report numeric ID validity with success/failure exit codes for scripting.
 */
final class SnowflakeValidateCommand extends Command
{
    protected $signature = 'snowflake:validate {id : The Snowflake ID to validate}';

    protected $description = 'Validate a Snowflake ID';

    public function handle(SnowflakeValidator $validator): int
    {
        $id = $this->argument('id');

        if (! $validator->isValid($id)) {
            $this->components->error('Invalid Snowflake ID.');

            return self::FAILURE;
        }

        $this->components->info('Valid Snowflake ID.');

        return self::SUCCESS;
    }
}
