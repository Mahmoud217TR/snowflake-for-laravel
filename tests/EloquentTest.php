<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MahmoudTR\Snowflake\Contracts\Clock;
use MahmoudTR\Snowflake\Exceptions\InvalidSnowflake;
use MahmoudTR\Snowflake\Tests\Support\FakeClock;
use MahmoudTR\Snowflake\Tests\Support\SnowflakeUser;

beforeEach(function () {
    $this->app->instance(Clock::class, new FakeClock(1767225600100));
    Schema::create('users', function (Blueprint $table) {
        $table->snowflake();
        $table->unsignedBigInteger('reference_id')->nullable();
        $table->string('name')->default('Test');
    });
});

it('automatically generates a primary key and exposes it as a string', function () {
    $user = SnowflakeUser::create();
    $id = $user->getKey();
    $user->refresh();

    expect($id)->toBe('419430400')
        ->and($user->getKey())->toBe($id)->toBeString()
        ->and($user->getKeyType())->toBe('string')
        ->and($user->getIncrementing())->toBeFalse()
        ->and(DB::table('users')->value('id'))->toBe(419430400)
        ->and(json_decode($user->toJson(), true)['id'])->toBe($id);
});

it('persists the maximum signed ID exactly without JavaScript precision loss', function () {
    $user = SnowflakeUser::create(['id' => '9223372036854775807']);
    $user->refresh();

    expect($user->getKey())->toBe('9223372036854775807')
        ->and(DB::table('users')->value('id'))->toBe(PHP_INT_MAX)
        ->and(json_decode($user->toJson(), true)['id'])->toBe('9223372036854775807');
});

it('casts assigned integer IDs and nullable attributes correctly', function () {
    $user = SnowflakeUser::create(['id' => 0, 'reference_id' => 419459079]);
    $user->refresh();

    expect($user->getKey())->toBe('0')
        ->and($user->reference_id)->toBe('419459079');

    $user->reference_id = null;
    $user->save();
    expect($user->fresh()->reference_id)->toBeNull();
});

it('rejects invalid manually assigned Snowflakes', function (mixed $id) {
    expect(fn () => SnowflakeUser::create(['id' => $id]))->toThrow(InvalidSnowflake::class);
})->with([['invalid'], ['-1'], ['1.5'], ['9223372036854775808'], [1.0], [[]]]);

it('resolves valid route bindings', function () {
    $user = SnowflakeUser::create();

    expect((new SnowflakeUser)->resolveRouteBinding($user->getKey())->getKey())->toBe($user->getKey())
        ->and((new SnowflakeUser)->resolveRouteBinding('123'))->toBeNull();
});

it('rejects invalid route bindings before querying', function (?string $field) {
    DB::enableQueryLog();

    expect(fn () => (new SnowflakeUser)->resolveRouteBinding('invalid', $field))->toThrow(ModelNotFoundException::class)
        ->and(DB::getQueryLog())->toBe([]);
})->with([null, 'id']);

it('does not validate route binding for non Snowflake columns', function () {
    $user = SnowflakeUser::create(['name' => 'example']);

    expect((new SnowflakeUser)->resolveRouteBinding('example', 'name')->getKey())->toBe($user->getKey());
});

it('creates a bigint primary key automatically', function () {
    $column = collect(Schema::getColumns('users'))->firstWhere('name', 'id');
    $primary = collect(Schema::getIndexes('users'))->firstWhere('primary', true);

    expect($column['type_name'])->toBe('integer')
        ->and($primary['columns'])->toBe(['id']);

    SnowflakeUser::create(['id' => '123']);
    expect(fn () => SnowflakeUser::create(['id' => '123']))->toThrow(QueryException::class);
});

it('supports a custom primary key name', function () {
    Schema::create('custom_users', fn (Blueprint $table) => $table->snowflake('snowflake_id'));
    DB::table('custom_users')->insert(['snowflake_id' => '123']);

    expect(DB::table('custom_users')->value('snowflake_id'))->toBe(123)
        ->and(collect(Schema::getIndexes('custom_users'))->firstWhere('primary', true)['columns'])->toBe(['snowflake_id']);
});

it('creates compatible constrained foreign Snowflake columns', function () {
    Schema::create('posts', function (Blueprint $table) {
        $table->snowflake();
        $table->foreignSnowflake('user_id')->constrained();
    });
    $user = SnowflakeUser::create();
    DB::table('posts')->insert(['id' => '1', 'user_id' => $user->getKey()]);
    $foreign = Schema::getForeignKeys('posts')[0];

    expect(collect(Schema::getColumns('posts'))->firstWhere('name', 'user_id')['type_name'])->toBe('integer')
        ->and($foreign['columns'])->toBe(['user_id'])
        ->and($foreign['foreign_table'])->toBe('users')
        ->and($foreign['foreign_columns'])->toBe(['id']);

    expect(fn () => DB::table('posts')->insert(['id' => '2', 'user_id' => '999']))->toThrow(QueryException::class);
});
