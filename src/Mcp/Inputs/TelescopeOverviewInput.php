<?php

namespace AlexeyPenkov\TelescopeMcp\Mcp\Inputs;

use Laravel\Mcp\Request;

readonly class TelescopeOverviewInput
{
    private function __construct(public int $hours) {}

    public static function make(Request $request): self
    {
        $validated = $request->validate([
            'hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ]);

        return new self((int) ($validated['hours'] ?? 24));
    }
}
