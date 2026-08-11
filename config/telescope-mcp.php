<?php

return [
    'local' => [
        'name' => env('TELESCOPE_MCP_NAME', 'telescope'),
    ],

    'web' => [
        'enabled' => env('TELESCOPE_MCP_WEB_ENABLED', false),
        'path' => env('TELESCOPE_MCP_WEB_PATH', '/mcp/telescope'),
        'token' => env('TELESCOPE_MCP_TOKEN'),
        'middleware' => [],
    ],

    'limits' => [
        'default' => 20,
        'maximum' => 100,
        'trace' => 100,
        'overview_scan' => 1000,
        'string_length' => 8000,
        'array_items' => 100,
        'depth' => 8,
    ],

    'sensitive_keys' => [
        'authorization',
        'cookie',
        'password',
        'passwd',
        'secret',
        'session',
        'token',
        'api_key',
        'private_key',
        'client_secret',
    ],
];
