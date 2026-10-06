<?php

declare(strict_types=1);

use Illuminate\Redis\RedisManager;
use MahmoudTR\Snowflake\Configuration\SnowflakeConfig;
use MahmoudTR\Snowflake\Contracts\Clock;
use MahmoudTR\Snowflake\Contracts\StateStore;
use MahmoudTR\Snowflake\Exceptions\ClockMovedBackwards;
use MahmoudTR\Snowflake\Identity\StaticGeneratorIdProvider;
use MahmoudTR\Snowflake\Redis\LuaScript;
use MahmoudTR\Snowflake\SnowflakeGenerator;
use MahmoudTR\Snowflake\SnowflakeParser;
use MahmoudTR\Snowflake\State\LocalStateStore;
use MahmoudTR\Snowflake\State\RedisStateStore;
use MahmoudTR\Snowflake\Tests\Support\FakeClock;
use Symfony\Component\Process\Process;

beforeEach(function () {
    if (getenv('RUN_REDIS_TESTS') !== '1') {
        $this->markTestSkipped('Set RUN_REDIS_TESTS=1 to run real Redis integration tests.');
    }

    $client = getenv('REDIS_CLIENT') ?: 'phpredis';
    if ($client === 'phpredis') {
        expect(extension_loaded('redis'))->toBeTrue('PhpRedis integration tests require the Redis extension.');
    }

    $this->prefix = 'snowflake:test:'.bin2hex(random_bytes(16));
    config()->set('database.redis', [
        'client' => $client,
        'options' => ['prefix' => ''],
        'default' => [
            'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('REDIS_PORT') ?: 6379),
            'database' => 0,
        ],
    ]);
    config()->set('snowflake.state.driver', 'redis');
    config()->set('snowflake.state.prefix', $this->prefix);
    $this->redis = app(RedisManager::class)->connection();
    $this->newStore = fn () => new RedisStateStore(
        new LuaScript($this->redis, file_get_contents(__DIR__.'/../resources/lua/next-state.lua')),
        $this->prefix,
    );
    $this->store = ($this->newStore)();
});

afterEach(function () {
    if (isset($this->redis, $this->prefix)) {
        $keys = $this->redis->keys($this->prefix.':*');
        if ($keys !== []) {
            $this->redis->del(...$keys);
        }
        $this->redis->disconnect();
    }
});

it('starts at zero and increments atomically', function () {
    $first = $this->store->next(0, 1767225600100);
    $second = $this->store->next(0, 1767225600100);

    expect([$first->timestamp, $first->sequence])->toBe([1767225600100, 0])
        ->and([$second->timestamp, $second->sequence])->toBe([1767225600100, 1]);
});

it('resets at a newer timestamp', function () {
    $this->store->next(0, 100);
    $this->store->next(0, 100);
    $state = $this->store->next(0, 101);

    expect([$state->timestamp, $state->sequence])->toBe([101, 0]);
});

it('preserves state on rollback', function () {
    $this->store->next(0, 100);
    $this->store->next(0, 100);
    $state = $this->store->next(0, 99);

    expect([$state->timestamp, $state->sequence])->toBe([100, 1])
        ->and($this->store->next(0, 100)->sequence)->toBe(2);
});

it('coordinates multiple store instances for the same generator', function () {
    $other = ($this->newStore)();

    expect($this->store->next(7, 100)->sequence)->toBe(0)
        ->and($other->next(7, 100)->sequence)->toBe(1)
        ->and($this->store->next(7, 100)->sequence)->toBe(2);
});

it('maintains independent state for different generators', function () {
    $this->store->next(0, 100);
    $this->store->next(0, 100);

    expect($this->store->next(1023, 100)->sequence)->toBe(0)
        ->and($this->store->next(0, 100)->sequence)->toBe(2);
});

it('matches local state behavior including sequence exhaustion', function () {
    $local = new LocalStateStore;
    foreach ([100, 100, 99, 101, 100, 101] as $timestamp) {
        expect($this->store->next(0, $timestamp))->toEqual($local->next(0, $timestamp));
    }
    for ($i = 0; $i < 4097; $i++) {
        $redisState = $this->store->next(1023, 200);
        $localState = $local->next(1023, 200);
    }

    expect($redisState)->toEqual($localState)
        ->and($redisState->sequence)->toBe(4096)
        ->and($this->store->next(1023, 201))->toEqual($local->next(1023, 201));
});

it('generates through the configured Redis driver with rollback and sequence exhaustion', function () {
    $clock = new FakeClock(1767225600100);
    $this->app->instance(Clock::class, $clock);
    expect(app(StateStore::class))->toBeInstanceOf(RedisStateStore::class);

    $first = snowflake()->generate();
    $clock->timestamp -= 5;
    $second = snowflake()->generate();
    expect($second)->not->toBe($first)
        ->and($clock->waits)->toBe([1767225600100])
        ->and(snowflake()->inspect($second)->sequence)->toBe(1);

    // Consume the remaining sequence values through the real generator.
    for ($i = 2; $i <= 4095; $i++) {
        snowflake()->generate();
    }
    $next = snowflake()->inspect(snowflake()->generate());
    expect($next->sequence)->toBe(0)
        ->and((int) $next->timestamp->getTimestampMs())->toBe(1767225600101)
        ->and($clock->waits)->toBe([1767225600100, 1767225600101]);

    $clock->timestamp -= 6;
    expect(fn () => snowflake()->generate())->toThrow(ClockMovedBackwards::class);
});

it('coordinates generator instances without duplicate IDs', function () {
    $clock = new FakeClock(1767225600100);
    $config = new SnowflakeConfig(1767225600000);
    $first = new SnowflakeGenerator($clock, new StaticGeneratorIdProvider(7), $this->store, $config);
    $second = new SnowflakeGenerator($clock, new StaticGeneratorIdProvider(7), ($this->newStore)(), $config);
    $parser = new SnowflakeParser($config);
    $ids = [$first->generate(), $second->generate(), $first->generate(), $second->generate()];

    expect(array_unique($ids))->toHaveCount(4)
        ->and(array_map(fn ($id) => $parser->parse($id)->sequence, $ids))->toBe([0, 1, 2, 3]);
});

it('never duplicates timestamp sequence pairs under concurrent access', function () {
    $processes = [];
    try {
        for ($worker = 0; $worker < 4; $worker++) {
            $process = new Process([
                PHP_BINARY, __DIR__.'/Support/redis-worker.php',
                (string) config('database.redis.default.host'),
                (string) config('database.redis.default.port'),
                $this->prefix,
                (string) config('database.redis.client'),
            ]);
            $process->setTimeout(60);
            $process->start();
            $processes[] = $process;
        }

        $deadline = microtime(true) + 30;
        while ((int) $this->redis->get($this->prefix.':ready') !== 4 && microtime(true) < $deadline) {
            usleep(1000);
        }
        expect((int) $this->redis->get($this->prefix.':ready'))->toBe(4);
        $this->redis->set($this->prefix.':start', '1');

        $pairs = [];
        foreach ($processes as $process) {
            expect($process->wait())->toBe(0, $process->getErrorOutput());
            $pairs = array_merge($pairs, json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR));
        }
        expect($pairs)->toHaveCount(4000)
            ->and(array_unique($pairs))->toHaveCount(4000);
        $sequences = array_map(fn ($pair) => (int) explode(':', $pair)[1], $pairs);
        sort($sequences);
        expect($sequences)->toBe(range(0, 3999));
    } finally {
        foreach ($processes as $process) {
            $process->stop();
        }
    }
})->group('redis');
