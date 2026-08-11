<?php

namespace Tests;

use AlexeyPenkov\TelescopeMcp\TelescopeMcpServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            TelescopeServiceProvider::class,
            TelescopeMcpServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('telescope.enabled', false);
        $app['config']->set('telescope.storage.database.connection', 'testing');
        $app['config']->set('telescope-mcp.web.enabled', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../vendor/laravel/telescope/database/migrations');
    }
}
