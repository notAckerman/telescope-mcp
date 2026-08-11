<?php

namespace AlexeyPenkov\TelescopeMcp\Mcp\Tools;

use AlexeyPenkov\TelescopeMcp\Actions\BuildTelescopeOverviewAction;
use AlexeyPenkov\TelescopeMcp\Mcp\Inputs\TelescopeOverviewInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Summarize recent Telescope telemetry by event type and count visible exception issues. The response says whether the configured storage scan covered the full time window. Use this first when triaging an application.')]
#[IsReadOnly, IsIdempotent, IsOpenWorld(false)]
class TelescopeOverview extends Tool
{
    public function __construct(private readonly BuildTelescopeOverviewAction $buildOverview) {}

    public function handle(Request $request): ResponseFactory
    {
        $input = TelescopeOverviewInput::make($request);
        $overview = $this->buildOverview->handle($input->hours);

        return Response::structured([
            'application' => (string) config('app.name'),
            'environment' => app()->environment(),
            ...$overview->toArray(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['hours' => $schema->integer()->description('Lookback window in hours, from 1 to 720.')->min(1)->max(720)->default(24)];
    }
}
