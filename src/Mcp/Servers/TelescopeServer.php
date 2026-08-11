<?php

namespace AlexeyPenkov\TelescopeMcp\Mcp\Servers;

use AlexeyPenkov\TelescopeMcp\Mcp\Tools\BrowseTelescopeIssues;
use AlexeyPenkov\TelescopeMcp\Mcp\Tools\InspectTelescopeEvent;
use AlexeyPenkov\TelescopeMcp\Mcp\Tools\InspectTelescopeTrace;
use AlexeyPenkov\TelescopeMcp\Mcp\Tools\InvestigateTelescopeIssue;
use AlexeyPenkov\TelescopeMcp\Mcp\Tools\SearchTelescopeEvents;
use AlexeyPenkov\TelescopeMcp\Mcp\Tools\TelescopeOverview;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

#[Name('Laravel Telescope')]
#[Version('1.0.0')]
#[Instructions('Read-only observability server for investigating a Laravel application. Start with telescope-overview or browse-telescope-issues. Use investigate-telescope-issue for an exception and its surrounding trace. Use search-telescope-events for focused browsing, inspect-telescope-event for full details, and inspect-telescope-trace to reconstruct a request, command, or job lifecycle. Entry content may be truncated and sensitive keys are always redacted.')]
class TelescopeServer extends Server
{
    /** @var array<int, class-string<Tool>> */
    protected array $tools = [
        TelescopeOverview::class,
        BrowseTelescopeIssues::class,
        InvestigateTelescopeIssue::class,
        SearchTelescopeEvents::class,
        InspectTelescopeEvent::class,
        InspectTelescopeTrace::class,
    ];
}
