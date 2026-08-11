<?php

namespace AlexeyPenkov\TelescopeMcp\Domain\Telescope;

readonly class TelescopeEntryFilter
{
    public function __construct(
        public ?EntryType $type = null,
        public ?string $tag = null,
        public ?string $batchId = null,
        public ?string $familyHash = null,
        public ?int $beforeSequence = null,
        public int $limit = 20,
    ) {}
}
