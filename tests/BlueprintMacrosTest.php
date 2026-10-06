<?php

declare(strict_types=1);

use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;

it('compiles compatible unsigned bigint primary and foreign keys for MySQL', function () {
    // Compile SQL without requiring a MySQL server; SQLite persistence is tested separately.
    $connection = new MySqlConnection(fn () => throw new LogicException('This test must not connect to MySQL.'));
    $connection->setSchemaGrammar(new MySqlGrammar($connection));
    $users = new Blueprint($connection, 'users');
    $users->create();
    $id = $users->snowflake();
    $posts = new Blueprint($connection, 'posts');
    $posts->create();
    $posts->snowflake();
    $foreign = $posts->foreignSnowflake('user_id');
    $foreign->constrained();

    expect($id->type)->toBe('bigInteger')
        ->and($id->unsigned)->toBeTrue()
        ->and($id->autoIncrement)->toBeFalse()
        ->and($foreign->type)->toBe('bigInteger')
        ->and($foreign->unsigned)->toBeTrue()
        ->and(implode(' ', $users->toSql()))->toContain('`id` bigint unsigned not null', 'primary key (`id`)')
        ->and(implode(' ', $posts->toSql()))->toContain('`user_id` bigint unsigned not null', 'references `users` (`id`)');
});
