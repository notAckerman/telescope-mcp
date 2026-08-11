<?php

namespace AlexeyPenkov\TelescopeMcp\Contracts;

use AlexeyPenkov\TelescopeMcp\Domain\Telescope\TelescopeEntryFilter;
use Illuminate\Support\Collection;
use Laravel\Telescope\EntryResult;

interface TelescopeEntryRepository
{
    /**
     * @return Collection<int, EntryResult>
     */
    public function list(TelescopeEntryFilter $filter): Collection;

    public function find(string $uuid): EntryResult;
}
