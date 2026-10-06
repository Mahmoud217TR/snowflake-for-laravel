<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use MahmoudTR\Snowflake\Contracts\Clock;
use MahmoudTR\Snowflake\Facades\Snowflake;
use MahmoudTR\Snowflake\Rules\Snowflake as SnowflakeRule;
use MahmoudTR\Snowflake\Tests\Support\FakeClock;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->app->instance(Clock::class, new FakeClock(1767225600100));
});

it('exposes generation inspection and validation through the facade and helper', function () {
    $first = Snowflake::generate();
    $second = snowflake()->generate();

    expect($first)->toBe('419430400')
        ->and($second)->toBe('419430401')
        ->and(Snowflake::inspect($first)->generatorId)->toBe(0)
        ->and(snowflake()->inspect($second)->sequence)->toBe(1)
        ->and(Snowflake::isValid($first))->toBeTrue()
        ->and(snowflake()->isValid($second))->toBeTrue()
        ->and(Snowflake::isValid('invalid'))->toBeFalse()
        ->and(snowflake()->isValid(null))->toBeFalse();
});

it('registers string and object validation rules', function (mixed $id, bool $valid) {
    foreach (['snowflake', new SnowflakeRule] as $rule) {
        $validator = Validator::make(['id' => $id], ['id' => ['required', $rule]]);

        expect($validator->passes())->toBe($valid);
        if (! $valid) {
            expect($validator->errors()->has('id'))->toBeTrue();
        }
    }
})->with([
    ['419430400', true], [419430400, true], ['0', true], [0, true],
    ['9223372036854775807', true], ['000123', true],
    ['invalid', false], ['9223372036854775808', false], [-1, false],
    [1.5, false], [[], false], [null, false],
]);

it('reports the validation error message', function () {
    foreach (['snowflake', new SnowflakeRule] as $rule) {
        expect(Validator::make(['id' => 'invalid'], ['id' => $rule])->errors()->first('id'))
            ->toBe('The id must be a valid Snowflake ID.');
    }
});

it('generates an ID through Artisan', function () {
    $this->artisan('snowflake:generate')->expectsOutput('419430400')->assertSuccessful();
});

it('inspects a Snowflake through Artisan', function () {
    expect(Artisan::call('snowflake:inspect', ['id' => '419459079']))->toBe(0);
    expect(Artisan::output())->toContain('419459079', '2026-01-01 00:00:00.100 UTC', 'Generator ID', 'Sequence')
        ->toMatch('/Generator ID[^\n]+7/')
        ->toMatch('/Sequence[^\n]+7/');
});

it('fails inspection for an invalid ID', function () {
    expect(Artisan::call('snowflake:inspect', ['id' => 'invalid']))->toBe(1);
    expect(Artisan::output())->toContain('Snowflake ID must contain only decimal digits.');
});

it('validates an ID through Artisan', function (string $id, int $code, string $message) {
    expect(Artisan::call('snowflake:validate', ['id' => $id]))->toBe($code);
    expect(Artisan::output())->toContain($message);
})->with([['419430400', 0, 'Valid Snowflake ID.'], ['invalid', 1, 'Invalid Snowflake ID.']]);

it('reports configuration through Artisan without consuming a sequence', function () {
    expect(Artisan::call('snowflake:status'))->toBe(0);
    expect(Artisan::output())->toContain('Epoch', 'Generator ID', 'LocalStateStore', '5 ms', '1,024', '4,095', 'Expires at');
    expect(snowflake()->inspect(snowflake()->generate())->sequence)->toBe(0);
});

it('returns failure exit codes and useful output for command configuration errors', function (string $command, string $generatorId, string $message) {
    $process = new Process([PHP_BINARY, __DIR__.'/../vendor/bin/testbench', $command], env: [
        'SNOWFLAKE_GENERATOR_ID' => $generatorId,
        'SNOWFLAKE_STATE_DRIVER' => 'local',
        'NO_COLOR' => '1',
    ]);
    $process->setTimeout(60);

    expect($process->run())->toBe(1)
        ->and($process->getOutput().$process->getErrorOutput())->toContain($message);
})->with([
    ['snowflake:generate', '-1', 'Generator ID must be between 0 and 1023'],
    ['snowflake:status', 'invalid', 'A valid Snowflake generator ID must be configured.'],
]);
