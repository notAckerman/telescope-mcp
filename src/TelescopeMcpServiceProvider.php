<?php

namespace AlexeyPenkov\TelescopeMcp;

use AlexeyPenkov\TelescopeMcp\Contracts\TelescopeEntryRepository;
use AlexeyPenkov\TelescopeMcp\Http\Middleware\EnsureTelescopeMcpTokenIsValid;
use AlexeyPenkov\TelescopeMcp\Mcp\Servers\TelescopeServer;
use AlexeyPenkov\TelescopeMcp\Repositories\TelescopeStorageEntryRepository;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;

class TelescopeMcpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/telescope-mcp.php', 'telescope-mcp');

        $this->app->bind(TelescopeEntryRepository::class, TelescopeStorageEntryRepository::class);
    }

    public function boot(): void
    {
        $this->registerMcpServers();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/telescope-mcp.php' => config_path('telescope-mcp.php'),
            ], 'telescope-mcp-config');
        }
    }

    private function registerMcpServers(): void
    {
        Mcp::local((string) config('telescope-mcp.local.name'), TelescopeServer::class);

        if (! config('telescope-mcp.web.enabled')) {
            return;
        }

        Mcp::web((string) config('telescope-mcp.web.path'), TelescopeServer::class)
            ->middleware([
                EnsureTelescopeMcpTokenIsValid::class,
                'throttle:60,1',
                ...config('telescope-mcp.web.middleware', []),
            ]);
    }
}
