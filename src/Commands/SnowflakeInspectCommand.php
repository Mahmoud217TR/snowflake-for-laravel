<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Commands;

use Illuminate\Console\Command;
use MahmoudTR\Snowflake\Exceptions\InvalidSnowflake;
use MahmoudTR\Snowflake\SnowflakeParser;

final class SnowflakeInspectCommand extends Command
{
    protected $signature = 'snowflake:inspect {id : The Snowflake ID to inspect}';

    protected $description = 'Decode and inspect a Snowflake ID';

    public function handle(SnowflakeParser $parser): int
    {
        $id = $this->argument('id');

        try {
            $parts = $parser->parse($id);
        } catch (InvalidSnowflake $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Snowflake', (string) $id);

        $this->components->twoColumnDetail('Timestamp', $parts->timestamp->format('Y-m-d H:i:s.v \U\T\C'));

        $this->components->twoColumnDetail('Generator ID', (string) $parts->generatorId);

        $this->components->twoColumnDetail('Sequence', (string) $parts->sequence);

        return self::SUCCESS;
    }
}
