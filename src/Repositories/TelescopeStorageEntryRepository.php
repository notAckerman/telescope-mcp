<?php

namespace AlexeyPenkov\TelescopeMcp\Repositories;

use AlexeyPenkov\TelescopeMcp\Contracts\TelescopeEntryRepository;
use AlexeyPenkov\TelescopeMcp\Domain\Telescope\TelescopeEntryFilter;
use Illuminate\Support\Collection;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\Storage\EntryQueryOptions;

class TelescopeStorageEntryRepository implements TelescopeEntryRepository
{
    public function __construct(private readonly EntriesRepository $entries) {}

    public function list(TelescopeEntryFilter $filter): Collection
    {
        $options = (new EntryQueryOptions)
            ->tag($filter->tag)
            ->batchId($filter->batchId)
            ->familyHash($filter->familyHash)
            ->beforeSequence($filter->beforeSequence)
            ->limit($filter->limit);

        return $this->entries->get($filter->type?->value, $options);
    }

    public function find(string $uuid): EntryResult
    {
        return $this->entries->find($uuid);
    }
}
