<?php

declare(strict_types=1);

use MahmoudTR\Snowflake\Configuration\SnowflakeConfig;
use MahmoudTR\Snowflake\Exceptions\InvalidSnowflake;
use MahmoudTR\Snowflake\SnowflakeParser;
use MahmoudTR\Snowflake\Validation\SnowflakeValidator;

dataset('valid Snowflakes', [
    'string' => ['419459079'],
    'integer' => [419459079],
    'zero integer' => [0],
    'zero string' => ['0'],
    'leading zeroes' => ['000419459079'],
    'all zeroes' => ['0000'],
    'maximum string' => ['9223372036854775807'],
    'maximum integer' => [PHP_INT_MAX],
    'padded maximum' => ['0009223372036854775807'],
]);

dataset('invalid Snowflake strings', [
    'negative integer' => [-1],
    'negative string' => ['-1'],
    'empty' => [''],
    'letters' => ['snowflake'],
    'decimal' => ['1.5'],
    'integer decimal' => ['1.0'],
    'exponent' => ['1e3'],
    'plus sign' => ['+1'],
    'whitespace' => [' 1'],
    'newline' => ["1\n"],
    'oversized' => ['9223372036854775808'],
    'very oversized' => ['9999999999999999999999999'],
    'padded oversized' => ['0009223372036854775808'],
]);

it('parses all fields independently of the generator', function () {
    $parser = new SnowflakeParser(new SnowflakeConfig(1767225600000));
    // 100 ms, generator 7, sequence 7, calculated independently.
    $parts = $parser->parse('419459079');

    expect((int) $parts->timestamp->getTimestampMs())->toBe(1767225600100)
        ->and($parts->timestamp->getOffset())->toBe(0)
        ->and($parts->generatorId)->toBe(7)
        ->and($parts->sequence)->toBe(7);
});

it('accepts valid strings and integers consistently', function (string|int $id) {
    $parser = new SnowflakeParser(new SnowflakeConfig(1000));
    $parts = $parser->parse($id);
    $normalized = (int) $id;

    expect((int) $parts->timestamp->getTimestampMs())->toBe(($normalized >> 22) + 1000)
        ->and($parts->generatorId)->toBe(($normalized >> 12) & 1023)
        ->and($parts->sequence)->toBe($normalized & 4095);
})->with('valid Snowflakes');

it('parses the full signed 64-bit upper boundary', function () {
    $parts = (new SnowflakeParser(new SnowflakeConfig(1000)))->parse('9223372036854775807');

    expect((int) $parts->timestamp->getTimestampMs())->toBe(2199023256551)
        ->and($parts->generatorId)->toBe(1023)
        ->and($parts->sequence)->toBe(4095);
});

it('rejects invalid IDs', function (string|int $id) {
    $parser = new SnowflakeParser(new SnowflakeConfig(0));

    expect(fn () => $parser->parse($id))->toThrow(InvalidSnowflake::class);
})->with('invalid Snowflake strings');

it('validates valid IDs', function (string|int $id) {
    $validator = new SnowflakeValidator(new SnowflakeParser(new SnowflakeConfig(0)));

    expect($validator->isValid($id))->toBeTrue();
})->with('valid Snowflakes');

it('validates invalid strings without throwing', function (string|int $id) {
    $validator = new SnowflakeValidator(new SnowflakeParser(new SnowflakeConfig(0)));

    expect($validator->isValid($id))->toBeFalse();
})->with('invalid Snowflake strings');

it('rejects non integer input types without coercion', function (mixed $id) {
    $validator = new SnowflakeValidator(new SnowflakeParser(new SnowflakeConfig(0)));

    expect($validator->isValid($id))->toBeFalse();
})->with(['null' => [null], 'array' => [[]], 'float' => [1.0], 'boolean' => [true], 'object' => [new stdClass]]);
