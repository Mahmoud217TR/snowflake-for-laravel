<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use MahmoudTR\Snowflake\Configuration\SnowflakeConfig;
use MahmoudTR\Snowflake\Contracts\GeneratorIdProvider;
use MahmoudTR\Snowflake\Contracts\StateStore;
use MahmoudTR\Snowflake\SnowflakeLayout;

final class SnowflakeStatusCommand extends Command
{
    protected $signature = 'snowflake:status';

    protected $description = 'Display the current Snowflake configuration';

    public function handle(
        SnowflakeConfig $config,
        GeneratorIdProvider $generatorIdProvider,
        StateStore $stateStore,
    ): int {
        $generatorId = $generatorIdProvider->id();

        $expiresAt = CarbonImmutable::createFromTimestampMsUTC($config->epoch + SnowflakeLayout::MAX_TIMESTAMP);

        $this->components->twoColumnDetail(
            'Epoch',
            CarbonImmutable::createFromTimestampMsUTC($config->epoch)->toIso8601String(),
        );

        $this->components->twoColumnDetail('Generator ID', (string) $generatorId);

        $this->components->twoColumnDetail('State store', $stateStore::class);

        $this->components->twoColumnDetail('Maximum rollback', "{$config->maxRollbackMs} ms");

        $this->components->twoColumnDetail('Maximum generators', number_format(SnowflakeLayout::MAX_GENERATOR_ID + 1));

        $this->components->twoColumnDetail('Maximum sequence', number_format(SnowflakeLayout::MAX_SEQUENCE));

        $this->components->twoColumnDetail('Expires at', $expiresAt->toIso8601String());

        return self::SUCCESS;
    }
}
