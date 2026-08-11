<?php

namespace AlexeyPenkov\TelescopeMcp\Mcp\Tools;

use AlexeyPenkov\TelescopeMcp\Contracts\TelescopeEntryRepository;
use AlexeyPenkov\TelescopeMcp\Domain\Telescope\EntryType;
use AlexeyPenkov\TelescopeMcp\Mcp\Inputs\SearchTelescopeEventsInput;
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

#[Description('Browse recent Telescope events with filters for event type, tag, batch, exception family, and cursor. Returns compact summaries suitable for investigation.')]
#[IsReadOnly, IsIdempotent, IsOpenWorld(false)]
class SearchTelescopeEvents extends Tool
{
    public function __construct(
        private readonly TelescopeEntryRepository $entries,
        private readonly TelescopeEntryPresenter $presenter,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        $input = SearchTelescopeEventsInput::make($request);
        $events = $this->entries->list($input->filter)
            ->map(fn ($entry): array => $this->presenter->summary($entry));

        return Response::structured([
            'events' => $events->all(),
            'next_before_sequence' => $events->last()['sequence'] ?? null,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('Telescope event type.')->enum(EntryType::values()),
            'tag' => $schema->string()->description('Telescope tag; comma-separated values are accepted.')->max(255),
            'batch_id' => $schema->string()->description('Only events from this execution batch.')->format('uuid'),
            'family_hash' => $schema->string()->description('Only events from this exception family.')->max(255),
            'before_sequence' => $schema->integer()->description('Cursor: return entries older than this sequence.')->min(1),
            'limit' => $schema->integer()->description('Maximum events to return.')->min(1)->max((int) config('telescope-mcp.limits.maximum', 100))->default((int) config('telescope-mcp.limits.default', 20)),
        ];
    }
}
