<?php

namespace Tests\Feature\Actions;

use AlexeyPenkov\TelescopeMcp\Actions\BuildTelescopeOverviewAction;
use AlexeyPenkov\TelescopeMcp\Contracts\TelescopeEntryRepository;
use AlexeyPenkov\TelescopeMcp\Domain\Telescope\TelescopeEntryFilter;
use Illuminate\Support\Str;
use Laravel\Telescope\EntryResult;
use Mockery\MockInterface;
use Tests\TestCase;

class BuildTelescopeOverviewActionTest extends TestCase
{
    public function test_it_builds_a_typed_report_from_the_repository_scan(): void
    {
        config()->set('telescope-mcp.limits.overview_scan', 2);

        $entries = collect([
            $this->entry('request', now()->subMinute(), 2),
            $this->entry('exception', now()->subMinutes(2), 1),
        ]);

        $repository = $this->mock(TelescopeEntryRepository::class, function (MockInterface $mock) use ($entries): void {
            $mock->shouldReceive('list')
                ->once()
                ->withArgs(fn (TelescopeEntryFilter $filter): bool => $filter->limit === 2)
                ->andReturn($entries);
        });

        $report = (new BuildTelescopeOverviewAction($repository))->handle(24);

        $this->assertSame(2, $report->total);
        $this->assertSame(1, $report->issues);
        $this->assertSame(['request' => 1, 'exception' => 1], $report->type);
        $this->assertFalse($report->isComplete);
    }

    private function entry(string $type, mixed $createdAt, int $sequence): EntryResult
    {
        return new EntryResult(
            (string) Str::uuid(),
            $sequence,
            (string) Str::uuid(),
            $type,
            null,
            [],
            $createdAt,
        );
    }
}
