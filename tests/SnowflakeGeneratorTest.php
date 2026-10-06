<?php

declare(strict_types=1);

use MahmoudTR\Snowflake\Configuration\SnowflakeConfig;
use MahmoudTR\Snowflake\Contracts\GeneratorIdProvider;
use MahmoudTR\Snowflake\Exceptions\ClockMovedBackwards;
use MahmoudTR\Snowflake\Exceptions\EpochNotReached;
use MahmoudTR\Snowflake\Exceptions\InvalidGeneratorId;
use MahmoudTR\Snowflake\Exceptions\TimestampExhausted;
use MahmoudTR\Snowflake\Identity\StaticGeneratorIdProvider;
use MahmoudTR\Snowflake\SnowflakeGenerator;
use MahmoudTR\Snowflake\SnowflakeParser;
use MahmoudTR\Snowflake\State\LocalStateStore;
use MahmoudTR\Snowflake\Tests\Support\FakeClock;
use MahmoudTR\Snowflake\Validation\SnowflakeValidator;

beforeEach(function () {
    $this->config = new SnowflakeConfig(epoch: 1767225600000);
    $this->clock = new FakeClock($this->config->epoch + 100);
    $this->store = new LocalStateStore;
    $this->generator = new SnowflakeGenerator($this->clock, new StaticGeneratorIdProvider(7), $this->store, $this->config);
    $this->parser = new SnowflakeParser($this->config);
});

it('generates valid string IDs and roundtrips all parts', function () {
    $id = $this->generator->generate();
    $parts = $this->parser->parse($id);

    expect($id)->toBeString()
        ->and((new SnowflakeValidator($this->parser))->isValid($id))->toBeTrue()
        ->and((int) $parts->timestamp->getTimestampMs())->toBe($this->clock->timestamp)
        ->and($parts->generatorId)->toBe(7)
        ->and($parts->sequence)->toBe(0);
});

it('increments at the same timestamp and resets at the next timestamp', function () {
    $first = $this->generator->generate();
    $second = $this->generator->generate();
    $this->clock->timestamp++;
    $third = $this->generator->generate();

    expect($this->parser->parse($first)->sequence)->toBe(0)
        ->and($this->parser->parse($second)->sequence)->toBe(1)
        ->and($this->parser->parse($third)->sequence)->toBe(0)
        ->and((int) $this->parser->parse($third)->timestamp->getTimestampMs())->toBe($this->clock->timestamp)
        ->and([$first, $second, $third])->toHaveCount(count(array_unique([$first, $second, $third])));
});

it('supports boundary generator IDs', function (int $generatorId) {
    $generator = new SnowflakeGenerator($this->clock, new StaticGeneratorIdProvider($generatorId), $this->store, $this->config);

    expect($this->parser->parse($generator->generate())->generatorId)->toBe($generatorId);
})->with([0, 1023]);

it('defensively rejects out of range IDs from custom providers', function (int $generatorId) {
    $provider = new class($generatorId) implements GeneratorIdProvider
    {
        public function __construct(private readonly int $generatorId) {}

        public function id(): int
        {
            return $this->generatorId;
        }
    };
    $generator = new SnowflakeGenerator($this->clock, $provider, $this->store, $this->config);

    expect(fn () => $generator->generate())->toThrow(InvalidGeneratorId::class);
})->with([-1, 1024]);

it('distinguishes generators at the same timestamp', function () {
    $other = new SnowflakeGenerator($this->clock, new StaticGeneratorIdProvider(8), $this->store, $this->config);

    expect($this->generator->generate())->not->toBe($other->generate());
});

it('generates zero at the epoch for generator zero', function () {
    $this->clock->timestamp = $this->config->epoch;
    $generator = new SnowflakeGenerator($this->clock, new StaticGeneratorIdProvider(0), $this->store, $this->config);

    expect($generator->generate())->toBe('0')
        ->and($generator->generate())->toBe('1');
});

it('rejects timestamps before the epoch', function () {
    $this->clock->timestamp = $this->config->epoch - 1;

    expect(fn () => $this->generator->generate())->toThrow(EpochNotReached::class);
});

it('rejects exhausted timestamps without poisoning shared state', function () {
    $this->clock->timestamp = $this->config->epoch + (1 << 41);

    expect(fn () => $this->generator->generate())->toThrow(TimestampExhausted::class);

    $this->clock->timestamp = $this->config->epoch + 100;
    expect($this->parser->parse($this->generator->generate())->sequence)->toBe(0);
});

it('generates the maximum signed 64-bit Snowflake', function () {
    $this->clock->timestamp = $this->config->epoch + (1 << 41) - 1;
    $generator = new SnowflakeGenerator($this->clock, new StaticGeneratorIdProvider(1023), $this->store, $this->config);
    for ($i = 0; $i < 4095; $i++) {
        $generator->generate();
    }

    expect($generator->generate())->toBe('9223372036854775807');
    expect(fn () => $generator->generate())->toThrow(TimestampExhausted::class);
});

it('waits and retries for clock rollback within tolerance', function (int $rollback) {
    $first = $this->generator->generate();
    $previous = $this->clock->timestamp;
    $this->clock->timestamp -= $rollback;
    $second = $this->generator->generate();

    expect($this->clock->waits)->toBe([$previous])
        ->and($second)->not->toBe($first)
        ->and($this->parser->parse($second)->sequence)->toBe(1)
        ->and((int) $this->parser->parse($second)->timestamp->getTimestampMs())->toBe($previous);
})->with([1, 5]);

it('rejects rollback beyond the configured tolerance', function () {
    $this->generator->generate();
    $this->clock->timestamp -= 6;

    expect(fn () => $this->generator->generate())->toThrow(ClockMovedBackwards::class)
        ->and($this->clock->waits)->toBe([]);
});

it('honors a zero rollback tolerance', function () {
    $generator = new SnowflakeGenerator($this->clock, new StaticGeneratorIdProvider(7), $this->store, new SnowflakeConfig($this->config->epoch, 0));
    $generator->generate();
    $this->clock->timestamp--;

    expect(fn () => $generator->generate())->toThrow(ClockMovedBackwards::class);
});

it('uses every sequence value then waits for the next millisecond', function () {
    $ids = [];
    for ($sequence = 0; $sequence <= 4095; $sequence++) {
        $id = $this->generator->generate();
        $ids[] = $id;
        expect($this->parser->parse($id)->sequence)->toBe($sequence);
    }

    expect(array_unique($ids))->toHaveCount(4096)
        ->and($this->clock->waits)->toBe([]);

    $previous = $this->clock->timestamp;
    $parts = $this->parser->parse($this->generator->generate());

    expect($this->clock->waits)->toBe([$previous + 1])
        ->and((int) $parts->timestamp->getTimestampMs())->toBe($previous + 1)
        ->and($parts->sequence)->toBe(0);
});
