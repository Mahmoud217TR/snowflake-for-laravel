<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake;

use Illuminate\Foundation\Application;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Validator;
use MahmoudTR\Snowflake\Clock\SystemClock;
use MahmoudTR\Snowflake\Commands\SnowflakeGenerateCommand;
use MahmoudTR\Snowflake\Commands\SnowflakeInspectCommand;
use MahmoudTR\Snowflake\Commands\SnowflakeStatusCommand;
use MahmoudTR\Snowflake\Commands\SnowflakeValidateCommand;
use MahmoudTR\Snowflake\Configuration\SnowflakeConfig;
use MahmoudTR\Snowflake\Contracts\Clock;
use MahmoudTR\Snowflake\Contracts\GeneratorIdProvider;
use MahmoudTR\Snowflake\Contracts\StateStore;
use MahmoudTR\Snowflake\Database\BlueprintMacros;
use MahmoudTR\Snowflake\Exceptions\UnsafeConfiguration;
use MahmoudTR\Snowflake\Identity\StaticGeneratorIdProvider;
use MahmoudTR\Snowflake\Redis\LuaScript;
use MahmoudTR\Snowflake\State\LocalStateStore;
use MahmoudTR\Snowflake\State\RedisStateStore;
use MahmoudTR\Snowflake\Validation\SnowflakeValidator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class SnowflakeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('snowflake-for-laravel')
            ->hasConfigFile('snowflake')
            ->hasCommands(
                SnowflakeStatusCommand::class,
                SnowflakeInspectCommand::class,
                SnowflakeGenerateCommand::class,
                SnowflakeValidateCommand::class,
            );
    }

    public function packageRegistered(): void
    {
        $this->registerSnowflakeConfig();
        $this->registerClock();
        $this->registerGeneratorIdProvider();
        $this->registerStateStore();

        $this->app->singleton(SnowflakeGenerator::class);
        $this->app->singleton(SnowflakeParser::class);
    }

    public function packageBooted(): void
    {
        BlueprintMacros::register();

        Validator::extend(
            'snowflake',
            fn (string $attribute, mixed $value): bool => app(SnowflakeValidator::class)->isValid($value),
            'The :attribute must be a valid Snowflake ID.',
        );
    }

    private function registerSnowflakeConfig(): void
    {
        $this->app->singleton(
            SnowflakeConfig::class,
            fn (): SnowflakeConfig => new SnowflakeConfig(
                epoch: (int) config('snowflake.epoch'),
                maxRollbackMs: (int) config('snowflake.max_rollback_ms', 5),
            ),
        );
    }

    private function registerClock(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);
    }

    private function registerGeneratorIdProvider(): void
    {
        $this->app->singleton(GeneratorIdProvider::class, function (): GeneratorIdProvider {
            $generatorId = config('snowflake.generator_id');

            if (
                $generatorId === null
                || filter_var(
                    $generatorId,
                    FILTER_VALIDATE_INT,
                ) === false
            ) {
                throw new UnsafeConfiguration('A valid Snowflake generator ID must be configured.');
            }

            return new StaticGeneratorIdProvider(generatorId: (int) $generatorId);
        },
        );
    }

    private function registerStateStore(): void
    {
        $this->app->singleton(StateStore::class, function (Application $app): StateStore {
            $driver = config('snowflake.state.driver', 'redis');

            return match ($driver) {
                'local' => new LocalStateStore,

                'redis' => new RedisStateStore(
                    new LuaScript(
                        redis: $app
                            ->make(RedisManager::class)
                            ->connection(config('snowflake.state.connection', 'default')),
                        script: file_get_contents(__DIR__.'/../resources/lua/next-state.lua'),
                    ),
                    (string) config('snowflake.state.prefix', 'snowflake:state'),
                ),

                default => throw new UnsafeConfiguration(
                    "Unsupported Snowflake state driver [{$driver}].",
                ),
            };
        },
        );
    }
}
