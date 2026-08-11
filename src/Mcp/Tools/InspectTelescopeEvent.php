<?php

namespace AlexeyPenkov\TelescopeMcp\Mcp\Tools;

use AlexeyPenkov\TelescopeMcp\Contracts\TelescopeEntryRepository;
use AlexeyPenkov\TelescopeMcp\Mcp\Inputs\InspectTelescopeEventInput;
use AlexeyPenkov\TelescopeMcp\Support\TelescopeEntryPresenter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Inspect full, redacted details for any Telescope event by UUID. Use its batch_id with inspect-telescope-trace to inspect the surrounding execution.')]
#[IsReadOnly, IsIdempotent, IsOpenWorld(false)]
class InspectTelescopeEvent extends Tool
{
    public function __construct(
        private readonly TelescopeEntryRepository $entries,
        private readonly TelescopeEntryPresenter $presenter,
    ) {}

    public function handle(Request $request): ResponseFactory|Response
    {
        $input = InspectTelescopeEventInput::make($request);

        try {
            $entry = $this->entries->find($input->id);
        } catch (ModelNotFoundException) {
            return Response::error('Telescope entry not found. It may have been pruned.');
        }

        return Response::structured(['entry' => $this->presenter->full($entry)]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('Telescope event UUID returned by a browse or search tool.')->format('uuid')->required(),
        ];
    }
}
