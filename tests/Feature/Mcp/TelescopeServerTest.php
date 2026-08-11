<?php

namespace Tests\Feature\Mcp;

use AlexeyPenkov\TelescopeMcp\Mcp\Servers\TelescopeServer;
use AlexeyPenkov\TelescopeMcp\Mcp\Tools\BrowseTelescopeIssues;
use AlexeyPenkov\TelescopeMcp\Mcp\Tools\InspectTelescopeEvent;
use AlexeyPenkov\TelescopeMcp\Mcp\Tools\InspectTelescopeTrace;
use AlexeyPenkov\TelescopeMcp\Mcp\Tools\InvestigateTelescopeIssue;
use AlexeyPenkov\TelescopeMcp\Mcp\Tools\SearchTelescopeEvents;
use AlexeyPenkov\TelescopeMcp\Mcp\Tools\TelescopeOverview;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\Storage\EntryQueryOptions;
use Mockery\MockInterface;
use Tests\TestCase;

class TelescopeServerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_overview_summarizes_recent_telemetry(): void
    {
        $batchId = (string) Str::uuid();
        $this->storeEntry('request', ['method' => 'GET', 'uri' => '/users'], $batchId);
        $this->storeEntry('exception', ['class' => 'RuntimeException', 'message' => 'Broken'], $batchId, 'family-a');

        TelescopeServer::tool(TelescopeOverview::class, ['hours' => 24])
            ->assertStructuredContent(fn ($json) => $json
                ->where('total', 2)
                ->where('issues', 1)
                ->where('scanned', 2)
                ->where('is_complete', true)
                ->where('type.request', 1)
                ->where('type.exception', 1)
                ->etc());
    }

    public function test_overview_reads_through_telescope_storage_contract(): void
    {
        $entry = new EntryResult(
            (string) Str::uuid(),
            1,
            (string) Str::uuid(),
            'redis',
            null,
            ['command' => 'GET app:users'],
            now(),
        );

        $this->mock(EntriesRepository::class, function (MockInterface $mock) use ($entry): void {
            $mock->shouldReceive('get')
                ->once()
                ->withArgs(fn (mixed $type, mixed $options): bool => $type === null && $options instanceof EntryQueryOptions)
                ->andReturn(collect([$entry]));
        });

        TelescopeServer::tool(TelescopeOverview::class, ['hours' => 1])
            ->assertStructuredContent(fn ($json) => $json
                ->where('total', 1)
                ->where('type.redis', 1)
                ->where('is_complete', true)
                ->etc());
    }

    public function test_issue_workflow_returns_occurrences_trace_and_redacts_secrets(): void
    {
        $batchId = (string) Str::uuid();
        $oldIssueId = $this->storeEntry(
            'exception',
            ['class' => 'RuntimeException', 'message' => 'Database unavailable', 'occurrences' => 1],
            (string) Str::uuid(),
            'family-a',
            false,
        );
        $issueId = $this->storeEntry(
            'exception',
            [
                'class' => 'RuntimeException',
                'message' => 'Database unavailable',
                'occurrences' => 2,
                'context' => ['api_token' => 'super-secret-value'],
                'trace' => [['file' => '/app/Service.php', 'line' => 42]],
            ],
            $batchId,
            'family-a',
        );
        $this->storeEntry('query', ['sql' => 'select * from users', 'time' => '18.20'], $batchId);

        TelescopeServer::tool(BrowseTelescopeIssues::class)
            ->assertSee([$issueId, 'RuntimeException: Database unavailable', '"occurrences":2']);

        TelescopeServer::tool(InvestigateTelescopeIssue::class, ['id' => $issueId])
            ->assertSee([$oldIssueId, 'select * from users', '[redacted]'])
            ->assertDontSee('super-secret-value');
    }

    public function test_trace_returns_only_events_from_requested_batch(): void
    {
        $batchId = (string) Str::uuid();
        $requestId = $this->storeEntry('request', ['method' => 'POST', 'uri' => '/orders'], $batchId);
        $queryId = $this->storeEntry('query', ['sql' => 'insert into orders values (1)', 'time' => '2.10'], $batchId);
        $otherId = $this->storeEntry('log', ['level' => 'info', 'message' => 'unrelated'], (string) Str::uuid());

        TelescopeServer::tool(InspectTelescopeTrace::class, ['batch_id' => $batchId])
            ->assertSee([$requestId, $queryId])
            ->assertDontSee($otherId)
            ->assertStructuredContent(fn ($json) => $json
                ->where('batch_id', $batchId)
                ->where('event_count', 2)
                ->has('events', 2));
    }

    public function test_events_can_be_filtered_by_type_and_paginated(): void
    {
        $batchId = (string) Str::uuid();
        $olderQueryId = $this->storeEntry('query', ['sql' => 'select 1', 'time' => '1.00'], $batchId);
        $newerQueryId = $this->storeEntry('query', ['sql' => 'select 2', 'time' => '2.00'], $batchId);
        $requestId = $this->storeEntry('request', ['method' => 'GET', 'uri' => '/health'], $batchId);
        $newerSequence = (int) DB::table('telescope_entries')->where('uuid', $newerQueryId)->value('sequence');

        TelescopeServer::tool(SearchTelescopeEvents::class, [
            'type' => 'query',
            'before_sequence' => $newerSequence,
            'limit' => 10,
        ])
            ->assertSee($olderQueryId)
            ->assertDontSee([$newerQueryId, $requestId])
            ->assertStructuredContent(fn ($json) => $json->has('events', 1)->etc());
    }

    public function test_redis_events_expose_command_and_duration(): void
    {
        $redisId = $this->storeEntry('redis', [
            'connection' => 'cache',
            'command' => 'GET app:users',
            'time' => '0.42',
        ], (string) Str::uuid());

        TelescopeServer::tool(SearchTelescopeEvents::class, ['type' => 'redis'])
            ->assertSee([$redisId, 'GET app:users', '"duration_ms":"0.42"']);
    }

    public function test_any_entry_can_be_read_in_detail_with_secrets_redacted(): void
    {
        $entryId = $this->storeEntry('request', [
            'method' => 'POST',
            'uri' => '/login',
            'payload' => ['password' => 'do-not-expose'],
        ], (string) Str::uuid());

        TelescopeServer::tool(InspectTelescopeEvent::class, ['id' => $entryId])
            ->assertSee([$entryId, '/login', '[redacted]'])
            ->assertDontSee('do-not-expose');
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function storeEntry(
        string $type,
        array $content,
        string $batchId,
        ?string $familyHash = null,
        bool $displayOnIndex = true,
    ): string {
        $uuid = (string) Str::uuid();

        DB::table('telescope_entries')->insert([
            'uuid' => $uuid,
            'batch_id' => $batchId,
            'family_hash' => $familyHash,
            'should_display_on_index' => $displayOnIndex,
            'type' => $type,
            'content' => json_encode($content, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);

        return $uuid;
    }
}
