# Laravel Telescope MCP

A read-only Model Context Protocol server that gives AI assistants a Nightwatch-like investigation workflow over data recorded by Laravel Telescope.

## Installation

Install the package in a Laravel application that already uses Telescope:

```bash
composer require --dev alexeypenkov/laravel-telescope-mcp
```

Laravel discovers the package service provider automatically. The package does not own Telescope's installation, migrations, watchers, pruning, or storage configuration.

Optionally publish the package configuration:

```bash
php artisan vendor:publish --tag=telescope-mcp-config
```

## Tools

- `telescope-overview` summarizes telemetry and visible exception issues for a lookback window.
- `browse-telescope-issues` returns recent exception issues with cursor pagination.
- `investigate-telescope-issue` returns an exception, recent occurrences, and its execution trace.
- `search-telescope-events` searches requests, queries, Redis commands, jobs, logs, mail, and other Telescope event types.
- `inspect-telescope-event` returns full, redacted details for one event.
- `inspect-telescope-trace` reconstructs events recorded under one Telescope batch ID.

All tools are read-only. Sensitive nested values are recursively redacted, and large payloads are bounded before they leave the server.

## Local MCP connection

The package registers a local MCP server named `telescope`. Point your MCP client at the host application's Artisan executable:

```json
{
  "mcpServers": {
    "telescope": {
      "command": "php",
      "args": ["/absolute/path/to/your-app/artisan", "mcp:start", "telescope"]
    }
  }
}
```

The local name can be changed with `TELESCOPE_MCP_NAME`.

## Storage independence

This package reads through Telescope's `Laravel\\Telescope\\Contracts\\EntriesRepository`. It has no Eloquent model or direct database query, so a compatible Redis-backed Telescope repository can be bound by the host application without changing the MCP tools.

Redis operations observed by Telescope are also searchable as telemetry:

```json
{ "type": "redis" }
```

## Optional HTTP transport

HTTP is disabled by default. If enabled, the endpoint always requires a bearer token and is rate-limited:

```dotenv
TELESCOPE_MCP_WEB_ENABLED=true
TELESCOPE_MCP_WEB_PATH=/mcp/telescope
TELESCOPE_MCP_TOKEN=use-a-long-random-secret
```

Additional middleware may be configured in the published `config/telescope-mcp.php` file.

## Development

```bash
composer test
composer test:format
```
