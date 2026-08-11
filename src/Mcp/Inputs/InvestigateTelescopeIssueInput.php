<?php

namespace AlexeyPenkov\TelescopeMcp\Mcp\Inputs;

use Laravel\Mcp\Request;

readonly class InvestigateTelescopeIssueInput
{
    private function __construct(public string $id) {}

    public static function make(Request $request): self
    {
        $validated = $request->validate(['id' => ['required', 'uuid']]);

        return new self($validated['id']);
    }
}
