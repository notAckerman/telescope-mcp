<?php

namespace AlexeyPenkov\TelescopeMcp\Mcp\Inputs;

use AlexeyPenkov\TelescopeMcp\Domain\Telescope\EntryType;
use AlexeyPenkov\TelescopeMcp\Domain\Telescope\TelescopeEntryFilter;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;

readonly class SearchTelescopeEventsInput
{
    private function __construct(public TelescopeEntryFilter $filter) {}

    public static function make(Request $request): self
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::enum(EntryType::class)],
            'tag' => ['nullable', 'string', 'max:255'],
            'batch_id' => ['nullable', 'uuid'],
            'family_hash' => ['nullable', 'string', 'max:255'],
            'before_sequence' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.config('telescope-mcp.limits.maximum', 100)],
        ]);

        return new self(new TelescopeEntryFilter(
            type: isset($validated['type']) ? EntryType::from($validated['type']) : null,
            tag: $validated['tag'] ?? null,
            batchId: $validated['batch_id'] ?? null,
            familyHash: $validated['family_hash'] ?? null,
            beforeSequence: $validated['before_sequence'] ?? null,
            limit: (int) ($validated['limit'] ?? config('telescope-mcp.limits.default', 20)),
        ));
    }
}
