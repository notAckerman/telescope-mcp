<?php

namespace AlexeyPenkov\TelescopeMcp\Mcp\Tools;

use AlexeyPenkov\TelescopeMcp\Contracts\TelescopeEntryRepository;
use AlexeyPenkov\TelescopeMcp\Domain\Telescope\EntryType;
use AlexeyPenkov\TelescopeMcp\Domain\Telescope\TelescopeEntryFilter;
use AlexeyPenkov\TelescopeMcp\Mcp\Inputs\InvestigateTelescopeIssueInput;
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

#[Description('Investigate a Telescope exception issue, including stack trace, source preview, previous occurrences, and events from the same execution batch.')]
#[IsReadOnly, IsIdempotent, IsOpenWorld(false)]
class InvestigateTelescopeIssue extends Tool
{
    public function __construct(
        private readonly TelescopeEntryRepository $entries,
        private readonly TelescopeEntryPresenter $presenter,
    ) {}

    public function handle(Request $request): ResponseFactory|Response
    {
        $input = InvestigateTelescopeIssueInput::make($request);

        try {
            $issue = $this->entries->find($input->id);
        } catch (ModelNotFoundException) {
            return Response::error('Telescope entry not found. It may have been pruned.');
        }

        if ($issue->type !== EntryType::Exception->value) {
            return Response::error('The requested Telescope entry is not an exception issue.');
        }

        $occurrences = $issue->familyHash
            ? $this->entries->list(new TelescopeEntryFilter(
                type: EntryType::Exception,
                familyHash: $issue->familyHash,
                limit: 10,
            ))
            : collect([$issue]);
        $trace = $this->entries->list(new TelescopeEntryFilter(
            batchId: $issue->batchId,
            limit: (int) config('telescope-mcp.limits.trace', 100),
        ));

        return Response::structured([
            'issue' => $this->presenter->full($issue),
            'recent_occurrences' => $occurrences->map(fn ($entry): array => $this->presenter->summary($entry))->all(),
            'trace' => $trace->map(fn ($entry): array => $this->presenter->summary($entry))->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->string()->description('UUID of the exception entry returned by browse-telescope-issues.')->format('uuid')->required()];
    }
}
