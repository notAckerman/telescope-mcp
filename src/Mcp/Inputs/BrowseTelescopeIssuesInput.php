<?php

namespace AlexeyPenkov\TelescopeMcp\Mcp\Inputs;

use AlexeyPenkov\TelescopeMcp\Domain\Telescope\EntryType;
use AlexeyPenkov\TelescopeMcp\Domain\Telescope\TelescopeEntryFilter;
use Laravel\Mcp\Request;

readonly class BrowseTelescopeIssuesInput
{
    private function __construct(public TelescopeEntryFilter $filter) {}

    public static function make(Request $request): self
    {
        $validated = $request->validate([
            'before_sequence' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.config('telescope-mcp.limits.maximum', 100)],
        ]);

        return new self(new TelescopeEntryFilter(
            type: EntryType::Exception,
            beforeSequence: $validated['before_sequence'] ?? null,
            limit: (int) ($validated['limit'] ?? config('telescope-mcp.limits.default', 20)),
        ));
    }
}
