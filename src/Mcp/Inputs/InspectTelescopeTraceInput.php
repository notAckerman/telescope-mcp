<?php

namespace AlexeyPenkov\TelescopeMcp\Mcp\Inputs;

use AlexeyPenkov\TelescopeMcp\Domain\Telescope\TelescopeEntryFilter;
use Laravel\Mcp\Request;

readonly class InspectTelescopeTraceInput
{
    private function __construct(public TelescopeEntryFilter $filter) {}

    public static function make(Request $request): self
    {
        $validated = $request->validate([
            'batch_id' => ['required', 'uuid'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.config('telescope-mcp.limits.trace', 100)],
        ]);

        return new self(new TelescopeEntryFilter(
            batchId: $validated['batch_id'],
            limit: (int) ($validated['limit'] ?? config('telescope-mcp.limits.trace', 100)),
        ));
    }
}
