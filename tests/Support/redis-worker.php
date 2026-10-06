<?php

declare(strict_types=1);

use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use MahmoudTR\Snowflake\Redis\LuaScript;
use MahmoudTR\Snowflake\State\RedisStateStore;
use Predis\Client;

require __DIR__.'/../../vendor/autoload.php';

if ($argv[4] === 'phpredis') {
    $client = new Redis;
    $client->connect($argv[1], (int) $argv[2]);
    $redis = new PhpRedisConnection($client);
} else {
    $redis = new PredisConnection(new Client(['host' => $argv[1], 'port' => (int) $argv[2]]));
}
$prefix = $argv[3];
$store = new RedisStateStore(
    new LuaScript($redis, file_get_contents(__DIR__.'/../../resources/lua/next-state.lua')),
    $prefix,
);

$redis->incr($prefix.':ready');
$deadline = microtime(true) + 30;
while (! $redis->exists($prefix.':start')) {
    if (microtime(true) >= $deadline) {
        throw new RuntimeException('Timed out waiting for the concurrency test barrier.');
    }
    usleep(1000);
}

$pairs = [];
for ($i = 0; $i < 1000; $i++) {
    $state = $store->next(7, 1767225600100);
    $pairs[] = $state->timestamp.':'.$state->sequence;
}
echo json_encode($pairs, JSON_THROW_ON_ERROR);
$redis->disconnect();
