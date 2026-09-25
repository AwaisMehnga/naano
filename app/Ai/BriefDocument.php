<?php

namespace App\Ai;

use InvalidArgumentException;

class BriefDocument
{
    /** @var array<string, mixed> */
    private array $brief;

    private bool $wasRead = false;

    /**
     * @var list<array{path: string, before: string, after: string}>
     */
    private array $patches = [];

    /**
     * @param  array<string, mixed>  $brief
     */
    public function __construct(array $brief = [])
    {
        $this->brief = self::normalize($brief);
    }

    public function wasRead(): bool
    {
        return $this->wasRead;
    }

    /**
     * @return array<string, mixed>|mixed
     */
    public function read(?string $path = null): mixed
    {
        $this->wasRead = true;

        if ($path === null || $path === '') {
            return $this->brief;
        }

        return $this->get($path);
    }

    /**
     * @return list<array{path: string, before: string, after: string}>
     */
    public function patches(): array
    {
        return $this->patches;
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return $this->brief;
    }

    public function edit(string $path, string $oldValue, string $newValue, bool $replaceAll = false): string
    {
        if (! $this->wasRead) {
            throw new InvalidArgumentException('Call read_brief before edit_brief.');
        }

        $path = trim($path);

        if ($path === '') {
            throw new InvalidArgumentException('Path is required.');
        }

        $current = $this->get($path);
        $currentString = $this->encode($current);

        if ($replaceAll) {
            if (! is_string($current)) {
                throw new InvalidArgumentException('replace_all only works on string fields.');
            }

            if (! str_contains($current, $oldValue)) {
                throw new InvalidArgumentException("old_value not found at {$path}.");
            }

            $next = str_replace($oldValue, $newValue, $current);
            $this->set($path, $next);
            $this->patches[] = [
                'path' => $path,
                'before' => $current,
                'after' => $next,
            ];

            return "Edited {$path}.\nBefore: {$current}\nAfter: {$next}";
        }

        if ($currentString !== $oldValue) {
            throw new InvalidArgumentException(
                "old_value does not match current value at {$path}. Current: {$currentString}"
            );
        }

        $decoded = $this->decode($newValue, $current);
        $this->set($path, $decoded);
        $after = $this->encode($decoded);
        $this->patches[] = [
            'path' => $path,
            'before' => $currentString,
            'after' => $after,
        ];

        return "Edited {$path}.\nBefore: {$currentString}\nAfter: {$after}";
    }

    /**
     * @param  array<string, mixed>  $brief
     * @return array<string, mixed>
     */
    public static function normalize(array $brief): array
    {
        $empty = self::empty();

        return [
            'context' => (string) ($brief['context'] ?? $empty['context']),
            'product' => (string) ($brief['product'] ?? $empty['product']),
            'differentiators' => self::stringList($brief['differentiators'] ?? $empty['differentiators']),
            'target' => (string) ($brief['target'] ?? $empty['target']),
            'pains' => self::stringList($brief['pains'] ?? $empty['pains']),
            'trigger' => (string) ($brief['trigger'] ?? $empty['trigger']),
            'key_message' => (string) ($brief['key_message'] ?? $empty['key_message']),
            'audience' => [
                'industries' => (string) data_get($brief, 'audience.industries', ''),
                'geographies' => (string) data_get($brief, 'audience.geographies', ''),
                'tone' => (string) data_get($brief, 'audience.tone', ''),
            ],
            'editorial' => [
                'do' => self::stringList(data_get($brief, 'editorial.do', [''])),
                'avoid' => self::stringList(data_get($brief, 'editorial.avoid', [''])),
            ],
            'references' => self::references($brief['references'] ?? $empty['references']),
            'angles' => self::angles($brief['angles'] ?? $empty['angles']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function empty(): array
    {
        return [
            'context' => '',
            'product' => '',
            'differentiators' => [''],
            'target' => '',
            'pains' => [''],
            'trigger' => '',
            'key_message' => '',
            'audience' => [
                'industries' => '',
                'geographies' => '',
                'tone' => '',
            ],
            'editorial' => [
                'do' => [''],
                'avoid' => [''],
            ],
            'references' => [
                ['quote' => '', 'structure' => ''],
            ],
            'angles' => [
                ['title' => '', 'hook' => '', 'format' => '', 'example' => ''],
            ],
        ];
    }

    private function get(string $path): mixed
    {
        $value = data_get($this->brief, $path);

        if ($value === null && ! $this->pathExists($path)) {
            throw new InvalidArgumentException("Unknown path: {$path}");
        }

        return $value;
    }

    private function set(string $path, mixed $value): void
    {
        if (! $this->pathExists($path) && data_get($this->brief, $path) === null) {
            // Allow setting numeric list indices that already exist via parent.
            $segments = explode('.', $path);
            $parent = implode('.', array_slice($segments, 0, -1));
            $leaf = end($segments);

            if ($parent !== '' && is_array(data_get($this->brief, $parent)) && is_numeric($leaf)) {
                data_set($this->brief, $path, $value);

                return;
            }

            if (! $this->isKnownRootPath($path)) {
                throw new InvalidArgumentException("Unknown path: {$path}");
            }
        }

        data_set($this->brief, $path, $value);
    }

    private function pathExists(string $path): bool
    {
        $segments = explode('.', $path);
        $cursor = $this->brief;

        foreach ($segments as $segment) {
            if (! is_array($cursor) || ! array_key_exists($segment, $cursor)) {
                return false;
            }

            $cursor = $cursor[$segment];
        }

        return true;
    }

    private function isKnownRootPath(string $path): bool
    {
        $roots = [
            'context', 'product', 'differentiators', 'target', 'pains', 'trigger', 'key_message',
            'audience', 'audience.industries', 'audience.geographies', 'audience.tone',
            'editorial', 'editorial.do', 'editorial.avoid',
            'references', 'angles',
        ];

        foreach ($roots as $root) {
            if ($path === $root || str_starts_with($path, $root.'.')) {
                return true;
            }
        }

        return false;
    }

    private function encode(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    private function decode(string $newValue, mixed $current): mixed
    {
        if (is_string($current)) {
            return $newValue;
        }

        $decoded = json_decode($newValue, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('new_value must be valid JSON for this field type.');
        }

        return $decoded;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $values): array
    {
        if (! is_array($values) || $values === []) {
            return [''];
        }

        return array_values(array_map(
            fn ($value): string => is_string($value) ? $value : (string) $value,
            $values,
        ));
    }

    /**
     * @return list<array{quote: string, structure: string}>
     */
    private static function references(mixed $values): array
    {
        if (! is_array($values) || $values === []) {
            return [['quote' => '', 'structure' => '']];
        }

        return array_values(array_map(
            fn ($item): array => [
                'quote' => (string) ($item['quote'] ?? ''),
                'structure' => (string) ($item['structure'] ?? ''),
            ],
            $values,
        ));
    }

    /**
     * @return list<array{title: string, hook: string, format: string, example: string}>
     */
    private static function angles(mixed $values): array
    {
        if (! is_array($values) || $values === []) {
            return [['title' => '', 'hook' => '', 'format' => '', 'example' => '']];
        }

        return array_values(array_map(
            fn ($item): array => [
                'title' => (string) ($item['title'] ?? ''),
                'hook' => (string) ($item['hook'] ?? ''),
                'format' => (string) ($item['format'] ?? ''),
                'example' => (string) ($item['example'] ?? ''),
            ],
            $values,
        ));
    }
}
