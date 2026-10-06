<?php

declare(strict_types=1);

use MahmoudTR\Snowflake\Configuration\SnowflakeConfig;
use MahmoudTR\Snowflake\Contracts\GeneratorIdProvider;
use MahmoudTR\Snowflake\Contracts\StateStore;
use MahmoudTR\Snowflake\Exceptions\InvalidGeneratorId;
use MahmoudTR\Snowflake\Exceptions\UnsafeConfiguration;
use MahmoudTR\Snowflake\Identity\StaticGeneratorIdProvider;
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
})->with([[null], ['invalid'], ['1.5'], ['1e2'], [true], [false], [1.0], [[]]]);

it('accepts non-negative integer configuration without coercion', function (mixed $value, int $expected) {
    config()->set('snowflake.epoch', $value);
    config()->set('snowflake.max_rollback_ms', $value);
    $config = app(SnowflakeConfig::class);

    expect($config->epoch)->toBe($expected)
        ->and($config->maxRollbackMs)->toBe($expected);
})->with([[1767225600000, 1767225600000], ['1767225600000', 1767225600000], [0, 0], ['0', 0], ['0005', 5], [PHP_INT_MAX, PHP_INT_MAX]]);

it('rejects malformed epoch and rollback configuration before conversion', function (string $key, mixed $value) {
    config()->set($key, $value);

    expect(fn () => app(SnowflakeConfig::class))->toThrow(UnsafeConfiguration::class, $key);
})->with(['snowflake.epoch', 'snowflake.max_rollback_ms'])->with([
    [-1], ['-1'], ['abc'], ['1.5'], ['1e3'], [''], [' 1'], ['+1'],
    [null], [true], [false], [1.0], [[]], ['9223372036854775808'],
]);

it('accepts boundary static generator IDs', function (int $id) {
    expect((new StaticGeneratorIdProvider($id))->id())->toBe($id);
})->with([0, 1023]);

it('rejects out of range static generator IDs at construction', function (int $id) {
    expect(fn () => new StaticGeneratorIdProvider($id))->toThrow(InvalidGeneratorId::class);
})->with([-1, 1024]);

it('rejects configured generator IDs before a provider can be consumed', function (int|string $id) {
    config()->set('snowflake.generator_id', $id);

    expect(fn () => app(GeneratorIdProvider::class))->toThrow(InvalidGeneratorId::class);
})->with([-1, '-1', 1024, '1024']);

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
