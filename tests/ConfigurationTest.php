<?php

declare(strict_types=1);

use MahmoudTR\Snowflake\Configuration\SnowflakeConfig;
use MahmoudTR\Snowflake\Contracts\GeneratorIdProvider;
use MahmoudTR\Snowflake\Contracts\StateStore;
use MahmoudTR\Snowflake\Exceptions\UnsafeConfiguration;
use MahmoudTR\Snowflake\SnowflakeGenerator;
use MahmoudTR\Snowflake\State\LocalStateStore;

it('rejects negative epoch or rollback tolerance', function (int $epoch, int $rollback) {
    expect(fn () => new SnowflakeConfig($epoch, $rollback))->toThrow(InvalidArgumentException::class);
})->with([[-1, 5], [0, -1]]);

it('accepts epoch and tolerance zero', function () {
    $config = new SnowflakeConfig(0, 0);

    expect($config->epoch)->toBe(0)->and($config->maxRollbackMs)->toBe(0);
});

it('rejects missing or malformed configured generator IDs', function (mixed $id) {
    config()->set('snowflake.generator_id', $id);

    expect(fn () => app(GeneratorIdProvider::class))->toThrow(UnsafeConfiguration::class);
})->with([[null], ['invalid'], ['1.5']]);

it('rejects unsupported state drivers', function () {
    config()->set('snowflake.state.driver', 'invalid');

    expect(fn () => app(StateStore::class))->toThrow(UnsafeConfiguration::class);
});

it('shares the configured generator and local store within an application', function () {
    expect(app(GeneratorIdProvider::class)->id())->toBe(0)
        ->and(app(StateStore::class))->toBeInstanceOf(LocalStateStore::class)
        ->and(app(StateStore::class))->toBe(app(StateStore::class))
        ->and(app(SnowflakeGenerator::class))->toBe(app(SnowflakeGenerator::class));
});
