<?php

namespace AlexeyPenkov\TelescopeMcp\Domain\Telescope;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Laravel\Telescope\EntryResult;

readonly class TelescopeOverviewReport
{
    /**
     * @param  array<string, int>  $type
     */
    private function __construct(
        public CarbonInterface $from,
        public CarbonInterface $to,
        public int $total,
        public int $issues,
        public array $type,
        public int $scanned,
        public bool $isComplete,
    ) {}

    /**
     * @param  Collection<int, EntryResult>  $scannedEntries
     */
    public static function make(Collection $scannedEntries, CarbonInterface $from, CarbonInterface $to, int $scanLimit): self
    {
        $entries = self::withinWindow($scannedEntries, $from, $to);

        return new self(
            from: $from,
            to: $to,
            total: $entries->count(),
            issues: self::countIssues($entries),
            type: self::countByType($entries),
            scanned: $scannedEntries->count(),
            isComplete: self::coversWindow($scannedEntries, $from, $scanLimit),
        );
    }

    /**
     * @return array{from: string, to: string, total: int, issues: int, type: array<string, int>, scanned: int, is_complete: bool}
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from->toIso8601String(),
            'to' => $this->to->toIso8601String(),
            'total' => $this->total,
            'issues' => $this->issues,
            'type' => $this->type,
            'scanned' => $this->scanned,
            'is_complete' => $this->isComplete,
        ];
    }

    /**
     * @param  Collection<int, EntryResult>  $entries
     * @return Collection<int, EntryResult>
     */
    private static function withinWindow(Collection $entries, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $entries->filter(
            fn (EntryResult $entry): bool => $entry->createdAt->betweenIncluded($from, $to),
        );
    }

    /**
     * @param  Collection<int, EntryResult>  $entries
     */
    private static function countIssues(Collection $entries): int
    {
        return $entries->where('type', EntryType::Exception->value)->count();
    }

    /**
     * @param  Collection<int, EntryResult>  $entries
     * @return array<string, int>
     */
    private static function countByType(Collection $entries): array
    {
        return $entries
            ->countBy(fn (EntryResult $entry): string => $entry->type)
            ->map(fn (int $count): int => $count)
            ->all();
    }

    /**
     * @param  Collection<int, EntryResult>  $entries
     */
    private static function coversWindow(Collection $entries, CarbonInterface $from, int $scanLimit): bool
    {
        if ($entries->count() < $scanLimit) {
            return true;
        }

        $oldestEntry = $entries->last();

        return $oldestEntry instanceof EntryResult
            && $oldestEntry->createdAt->lessThanOrEqualTo($from);
    }
}
