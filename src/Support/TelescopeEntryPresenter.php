<?php

namespace AlexeyPenkov\TelescopeMcp\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Telescope\EntryResult;

class TelescopeEntryPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function summary(EntryResult $entry): array
    {
        $content = $entry->content;

        return array_filter([
            'id' => $entry->id,
            'sequence' => $entry->sequence,
            'batch_id' => $entry->batchId,
            'type' => $entry->type,
            'title' => $this->title($entry->type, $content),
            'status' => $this->status($entry->type, $content),
            'duration_ms' => $content['duration'] ?? $content['time'] ?? null,
            'occurrences' => $content['occurrences'] ?? null,
            'family_hash' => $entry->familyHash,
            'created_at' => $entry->createdAt->toIso8601String(),
        ], static fn (mixed $value): bool => ! blank($value));
    }

    /**
     * @return array<string, mixed>
     */
    public function full(EntryResult $entry): array
    {
        return $this->sanitize($entry->jsonSerialize());
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function title(string $type, array $content): string
    {
        return match ($type) {
            'exception' => trim(($content['class'] ?? 'Exception').': '.($content['message'] ?? '')),
            'request' => trim(($content['method'] ?? 'HTTP').' '.($content['uri'] ?? '/')),
            'query' => (string) ($content['sql'] ?? 'Database query'),
            'job' => (string) ($content['display_name'] ?? $content['name'] ?? 'Queued job'),
            'log' => trim(Str::upper((string) ($content['level'] ?? 'log')).': '.($content['message'] ?? '')),
            'client_request' => trim(($content['method'] ?? 'HTTP').' '.($content['uri'] ?? $content['url'] ?? '')),
            'command' => (string) ($content['command'] ?? 'Artisan command'),
            'redis' => (string) ($content['command'] ?? 'Redis command'),
            'cache' => trim(Str::upper((string) ($content['type'] ?? 'cache')).' '.($content['key'] ?? '')),
            'event' => (string) ($content['name'] ?? 'Event'),
            'view' => (string) ($content['name'] ?? $content['path'] ?? 'View'),
            default => (string) ($content['name'] ?? Str::headline($type)),
        };
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function status(string $type, array $content): int|string|null
    {
        return match ($type) {
            'request', 'client_request' => $content['response_status'] ?? null,
            'job' => $content['status'] ?? null,
            'command' => $content['exit_code'] ?? null,
            'log' => $content['level'] ?? null,
            default => null,
        };
    }

    private function sanitize(mixed $value, int $depth = 0): mixed
    {
        if ($depth >= (int) config('telescope-mcp.limits.depth', 8)) {
            return '[truncated: maximum depth reached]';
        }

        if (is_string($value)) {
            return Str::limit($value, (int) config('telescope-mcp.limits.string_length', 8000));
        }

        if (! is_array($value)) {
            return $value;
        }

        $result = [];
        $maximumItems = (int) config('telescope-mcp.limits.array_items', 100);

        foreach (array_slice($value, 0, $maximumItems, true) as $key => $item) {
            $result[$key] = is_string($key) && $this->isSensitive($key)
                ? '[redacted]'
                : $this->sanitize($item, $depth + 1);
        }

        if (count($value) > $maximumItems) {
            $result['_truncated_items'] = count($value) - $maximumItems;
        }

        return $result;
    }

    private function isSensitive(string $key): bool
    {
        $normalized = Str::of($key)->snake()->lower()->toString();

        return collect(Arr::wrap(config('telescope-mcp.sensitive_keys', [])))
            ->contains(fn (string $sensitive): bool => Str::contains($normalized, Str::lower($sensitive)));
    }
}
