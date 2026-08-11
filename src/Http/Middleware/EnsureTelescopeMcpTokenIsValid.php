<?php

namespace AlexeyPenkov\TelescopeMcp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTelescopeMcpTokenIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('telescope-mcp.web.token');
        $providedToken = (string) $request->bearerToken();

        abort_unless(
            filled($configuredToken) && filled($providedToken) && hash_equals($configuredToken, $providedToken),
            401,
            'Invalid MCP bearer token.',
        );

        return $next($request);
    }
}
