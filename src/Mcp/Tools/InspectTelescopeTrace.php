<?php

namespace AlexeyPenkov\TelescopeMcp\Mcp\Tools;

use AlexeyPenkov\TelescopeMcp\Contracts\TelescopeEntryRepository;
use AlexeyPenkov\TelescopeMcp\Mcp\Inputs\InspectTelescopeTraceInput;
use AlexeyPenkov\TelescopeMcp\Support\TelescopeEntryPresenter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Reconstruct an execution trace from a Telescope batch ID and return full, redacted details for its requests, queries, jobs, logs, exceptions, and other events.')]
#[IsReadOnly, IsIdempotent, IsOpenWorld(false)]
class InspectTelescopeTrace extends Tool
{
    public function __construct(
        private readonly TelescopeEntryRepository $entries,
        private readonly TelescopeEntryPresenter $presenter,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        $input = InspectTelescopeTraceInput::make($request);
        $events = $this->entries->list($input->filter);

        return Response::structured([
            'batch_id' => $input->filter->batchId,
            'event_count' => $events->count(),
            'events' => $events->map(fn ($entry): array => $this->presenter->full($entry))->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'batch_id' => $schema->string()->description('Execution batch UUID returned by another Telescope tool.')->format('uuid')->required(),
            'limit' => $schema->integer()->description('Maximum related events to return.')->min(1)->max((int) config('telescope-mcp.limits.trace', 100))->default((int) config('telescope-mcp.limits.trace', 100)),
        ];
    }
}
