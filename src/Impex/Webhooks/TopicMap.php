<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Webhooks;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\ConnectionInterface;
use RefactorCircus\Showroom\Support\Tables;
use stdClass;

/**
 * Divides a product into the topics subscribers choose from, as
 * `showroom.impex.webhooks.topics` describes them.
 *
 * Each topic claims attribute values by attribute type (`types`) or group
 * (`groups`), product fields (`fields`), and linked assets (`assets`). What no
 * topic claims falls to the `default` one, so every part of a product is in
 * exactly one topic.
 */
final class TopicMap
{
    /**
     * @var array<string, array{type: string, group: string|null}>|null
     */
    private ?array $attributes = null;

    private float $loadedAt = 0.0;

    public function __construct(
        private readonly Config $config,
        private readonly ConnectionInterface $db,
    ) {}

    /**
     * The topic names, in the order their bits are stored. Append new ones;
     * never reorder or remove one.
     *
     * @return list<string>
     */
    public function topics(): array
    {
        return array_map('strval', array_keys($this->definitions()));
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, array<string, mixed>|null>
     */
    public function slice(array $snapshot): array
    {
        $parts = [];
        $default = $this->defaultTopic();

        foreach (['family', 'parent', 'owner', 'enabled', 'categories', 'associations', 'quantified_associations'] as $field) {
            $parts[$this->fieldTopic($field) ?? $default]['fields'][$field] = $snapshot[$field] ?? null;
        }

        /** @var array<string, mixed> $values */
        $values = is_array($snapshot['values'] ?? null) ? $snapshot['values'] : [];

        foreach ($values as $code => $value) {
            $parts[$this->attributeTopic((string) $code) ?? $default]['values'][(string) $code] = $value;
        }

        $assets = $snapshot['assets'] ?? [];

        if (is_array($assets) && $assets !== []) {
            $parts[$this->assetTopic() ?? $default]['assets'] = $assets;
        }

        // A topic the product has nothing in is null, not an empty slice.
        $slices = [];

        foreach ($this->topics() as $topic) {
            $slices[$topic] = $parts[$topic] ?? null;
        }

        return $slices;
    }

    /**
     * Forget the cached attribute types and groups.
     */
    public function flush(): void
    {
        $this->attributes = null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function definitions(): array
    {
        /** @var array<string, array<string, mixed>> $topics */
        $topics = $this->config->get('showroom.impex.webhooks.topics', []);

        return $topics;
    }

    private function defaultTopic(): string
    {
        $topics = $this->definitions();

        foreach ($topics as $name => $definition) {
            if (($definition['default'] ?? false) === true) {
                return (string) $name;
            }
        }

        return (string) array_key_last($topics);
    }

    private function fieldTopic(string $field): ?string
    {
        foreach ($this->definitions() as $name => $definition) {
            if (in_array($field, (array) ($definition['fields'] ?? []), true)) {
                return (string) $name;
            }
        }

        return null;
    }

    private function assetTopic(): ?string
    {
        foreach ($this->definitions() as $name => $definition) {
            if (($definition['assets'] ?? false) === true) {
                return (string) $name;
            }
        }

        return null;
    }

    private function attributeTopic(string $code): ?string
    {
        $attribute = $this->attributes()[$code] ?? null;

        if ($attribute === null) {
            return null;
        }

        foreach ($this->definitions() as $name => $definition) {
            if (in_array($attribute['type'], (array) ($definition['types'] ?? []), true)
                || ($attribute['group'] !== null && in_array($attribute['group'], (array) ($definition['groups'] ?? []), true))) {
                return (string) $name;
            }
        }

        return null;
    }

    /**
     * Every attribute's type and group code, read once a minute at most: a
     * detection pass slices thousands of products against the same handful.
     *
     * @return array<string, array{type: string, group: string|null}>
     */
    private function attributes(): array
    {
        if ($this->attributes !== null && microtime(true) - $this->loadedAt < 60) {
            return $this->attributes;
        }

        $attributes = [];

        $rows = $this->db->table(Tables::name('attributes').' as a')
            ->leftJoin(Tables::name('attribute_groups').' as g', 'g.id', '=', 'a.attribute_group_id')
            ->get(['a.code', 'a.type', 'g.code as group_code']);

        foreach ($rows as $row) {
            /** @var stdClass $row */
            $attributes[(string) $row->code] = [
                'type' => (string) $row->type,
                'group' => is_string($row->group_code) ? $row->group_code : null,
            ];
        }

        $this->loadedAt = microtime(true);

        return $this->attributes = $attributes;
    }
}
