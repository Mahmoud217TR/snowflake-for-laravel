<?php

declare(strict_types=1);

namespace MahmoudTR\Snowflake\Tests;

use MahmoudTR\Snowflake\SnowflakeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            SnowflakeServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('snowflake.generator_id', 0);
        $app['config']->set('snowflake.state.driver', 'local');
    }
}
