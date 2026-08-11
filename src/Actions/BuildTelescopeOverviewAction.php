<?php

namespace AlexeyPenkov\TelescopeMcp\Actions;

use AlexeyPenkov\TelescopeMcp\Contracts\TelescopeEntryRepository;
use AlexeyPenkov\TelescopeMcp\Domain\Telescope\TelescopeEntryFilter;
use AlexeyPenkov\TelescopeMcp\Domain\Telescope\TelescopeOverviewReport;

class BuildTelescopeOverviewAction
{
    public function __construct(private readonly TelescopeEntryRepository $entries) {}

    public function handle(int $hours): TelescopeOverviewReport
    {
        $to = now();
        $from = $to->copy()->subHours($hours);
        $limit = (int) config('telescope-mcp.limits.overview_scan', 1000);
        $entries = $this->entries->list(new TelescopeEntryFilter(limit: $limit));

        return TelescopeOverviewReport::make($entries, $from, $to, $limit);
    }
}
