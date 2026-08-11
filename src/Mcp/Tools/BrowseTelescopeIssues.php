<?php

namespace AlexeyPenkov\TelescopeMcp\Mcp\Tools;

use AlexeyPenkov\TelescopeMcp\Contracts\TelescopeEntryRepository;
use AlexeyPenkov\TelescopeMcp\Mcp\Inputs\BrowseTelescopeIssuesInput;
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

#[Description('List recent exception issues grouped by Telescope family hash. Results include occurrence count, source location, and IDs for deeper investigation.')]
#[IsReadOnly, IsIdempotent, IsOpenWorld(false)]
class BrowseTelescopeIssues extends Tool
{
    public function __construct(
        private readonly TelescopeEntryRepository $entries,
        private readonly TelescopeEntryPresenter $presenter,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        $input = BrowseTelescopeIssuesInput::make($request);
        $issues = $this->entries->list($input->filter)
            ->map(fn ($entry): array => $this->presenter->summary($entry));

        return Response::structured([
            'issues' => $issues->all(),
            'next_before_sequence' => $issues->last()['sequence'] ?? null,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'before_sequence' => $schema->integer()->description('Return issues older than this sequence number.')->min(1),
            'limit' => $schema->integer()->description('Maximum issues to return.')->min(1)->max((int) config('telescope-mcp.limits.maximum', 100))->default((int) config('telescope-mcp.limits.default', 20)),
        ];
    }
}
