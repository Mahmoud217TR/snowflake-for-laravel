<?php

declare(strict_types=1);

use MahmoudTR\Snowflake\State\LocalStateStore;

it('starts at zero and increments at the same timestamp', function () {
    $store = new LocalStateStore;
    $first = $store->next(0, 100);
    $second = $store->next(0, 100);

    expect([$first->timestamp, $first->sequence])->toBe([100, 0])
        ->and([$second->timestamp, $second->sequence])->toBe([100, 1]);
});

it('resets sequence for a newer timestamp', function () {
    $store = new LocalStateStore;
    $store->next(0, 100);
    $store->next(0, 100);
    $state = $store->next(0, 101);

    expect([$state->timestamp, $state->sequence])->toBe([101, 0]);
});

it('returns the previous state unchanged on rollback', function () {
    $store = new LocalStateStore;
    $store->next(0, 100);
    $previous = $store->next(0, 100);

    expect($store->next(0, 99))->toBe($previous)
        ->and($store->next(0, 100)->sequence)->toBe(2);
});

it('isolates state by generator ID', function () {
    $store = new LocalStateStore;
    $store->next(0, 100);
    $store->next(0, 100);

    expect($store->next(1023, 100)->sequence)->toBe(0)
        ->and($store->next(0, 100)->sequence)->toBe(2);
});
