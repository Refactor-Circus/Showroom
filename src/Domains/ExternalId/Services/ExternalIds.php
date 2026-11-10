<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ExternalId\Services;

use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Support\KeyNormalizer;

/**
 * Products' and owners' ids in other systems, as a `{source: id}` map, named
 * and tracked as Roster's organization links are.
 *
 * An id is unique per kind of record and source: a product and an owner may
 * share one, two products may not. A record holds one id per source. The
 * database's unique indexes enforce both, the first under the column's
 * collation (case- and accent-insensitive by default); what this service
 * matches in memory it matches the same way, through KeyNormalizer.
 *
 * Each id keeps `synced_at`, when its source last wrote or confirmed it:
 * write() stamps every id it is sent, and touch() stamps the ids of records a
 * sync found unchanged.
 *
 * The rows are polymorphic, so no foreign key removes them with their
 * record; the Actions that delete products and owners call forget().
 */
final class ExternalIds
{
    /**
     * A source name: lowercase letters, digits, dashes and underscores.
     */
    public const string SOURCE_PATTERN = '/^[a-z0-9_-]{1,50}$/D';

    /**
     * The `external_ids` input of a create or an update: a `{source: id}`
     * map. On update only the sources sent change, and null removes one.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'external_ids' => ['sometimes', 'array', self::sources(...)],
            'external_ids.*' => ['nullable', 'string', 'max:191'],
        ];
    }

    /**
     * The `external` filter of a listing: `external[{source}]={id}`, every
     * pair matched.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function filterRules(): array
    {
        return [
            'external' => ['sometimes', 'array', self::sources(...)],
            'external.*' => ['required', 'string', 'max:191'],
        ];
    }

    /**
     * Refuse a list, or a key that is not a source name.
     */
    private static function sources(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        if ($value !== [] && array_is_list($value)) {
            $fail(sprintf('The %s field must be a {source: id} map.', $attribute));

            return;
        }

        foreach (array_keys($value) as $source) {
            if (preg_match(self::SOURCE_PATTERN, (string) $source) !== 1) {
                $fail(sprintf('The %s source "%s" must be 1 to 50 lowercase letters, digits, dashes or underscores.', $attribute, $source));
            }
        }
    }

    /**
     * The `{source: id}` map of a record's external id rows, by source.
     *
     * @param  iterable<int, ExternalIdModel>  $rows
     * @return array<string, string>
     */
    public static function present(iterable $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $map[$row->source] = $row->external_id;
        }

        ksort($map, SORT_STRING);

        return $map;
    }

    /**
     * When each source last synced a record's id, `{source: ISO-8601|null}`,
     * by source; null for an id no sync has stamped.
     *
     * @param  iterable<int, ExternalIdModel>  $rows
     * @return array<string, string|null>
     */
    public static function syncedAt(iterable $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $map[$row->source] = $row->synced_at?->toIso8601String();
        }

        ksort($map, SORT_STRING);

        return $map;
    }

    /**
     * Apply a `{source: id}` map to a record: each source sent is set, null
     * removes it, and sources not sent are kept. Every id sent is stamped
     * synced now, whether it changed or not. Call it inside the write's
     * transaction, so a refusal leaves nothing written.
     *
     * @param  array<array-key, string|null>  $map
     *
     * @throws ValidationException When another record of the same kind holds a (source, id), at `external_ids.{source}`.
     */
    public function write(Model $subject, array $map): void
    {
        if ($map === []) {
            return;
        }

        $type = $subject->getMorphClass();
        $subjectId = (string) $subject->getKey();
        $now = Carbon::now();

        /** @var array<string, string> $wanted */
        $wanted = [];

        foreach ($map as $source => $externalId) {
            if ($externalId !== null) {
                $wanted[(string) $source] = $externalId;
            }
        }

        $this->refuseHeld($subject, $wanted);

        /** @var array<string, ExternalIdModel> $current */
        $current = ExternalIdModel::query()
            ->where('subject_type', $type)
            ->where('subject_id', $subjectId)
            ->whereIn('source', array_map('strval', array_keys($map)))
            ->get()
            ->keyBy('source')
            ->all();

        /** @var list<string> $confirmed */
        $confirmed = [];

        foreach ($map as $source => $externalId) {
            $source = (string) $source;
            $row = $current[$source] ?? null;

            if ($externalId === null) {
                $row?->delete();

                continue;
            }

            if ($row !== null && $row->external_id === $externalId) {
                $confirmed[] = $row->id;

                continue;
            }

            $row ??= new ExternalIdModel(['subject_type' => $type, 'subject_id' => $subjectId, 'source' => $source]);
            $row->external_id = $externalId;
            $row->synced_at = $now;

            try {
                $row->save();
            } catch (UniqueConstraintViolationException) {
                // Taken between the check and the write.
                throw self::held($subject, $source, $externalId);
            }
        }

        // Sent again unchanged: only the sync stamp moves.
        if ($confirmed !== []) {
            ExternalIdModel::query()->whereKey($confirmed)->toBase()->update(['synced_at' => $now]);
        }
    }

    /**
     * Stamp synced now on every external id of many records of one kind, in
     * one update: their sources confirmed them unchanged. The ids and their
     * `updated_at` stay as they are, and no model events fire.
     *
     * @param  array<int, string>  $subjectIds
     * @param  class-string<Model>|string  $subjectType  A model class, or the morph alias its rows are stored under.
     */
    public function touch(array $subjectIds, string $subjectType): void
    {
        if ($subjectIds === []) {
            return;
        }

        ExternalIdModel::query()
            ->where('subject_type', self::morphClass($subjectType))
            ->whereIn('subject_id', array_values(array_unique($subjectIds)))
            ->toBase()
            ->update(['synced_at' => Carbon::now()]);
    }

    /**
     * Eager-load the external ids of many records of one kind, in one query.
     *
     * @param  EloquentCollection<int, covariant Model>  $subjects
     */
    public function load(EloquentCollection $subjects): void
    {
        $subjects->loadMissing('externalIds');
    }

    /**
     * The records of one kind holding ids in a source: each id asked for that
     * is held, as sent, to its holder's key. Ids that differ only by case or
     * accent match, as the database compares them.
     *
     * @param  class-string<Model>|string  $type  A model class, or the morph alias its rows are stored under.
     * @param  array<int, string>  $ids
     * @return array<string, string>
     */
    public function find(string $type, string $source, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $holders = [];

        foreach (ExternalIdModel::query()
            ->where('subject_type', self::morphClass($type))
            ->where('source', $source)
            ->whereIn('external_id', array_values(array_unique($ids)))
            ->get(['external_id', 'subject_id']) as $row) {
            $holders[KeyNormalizer::normalize($row->external_id)] = $row->subject_id;
        }

        $found = [];

        foreach ($ids as $id) {
            $holder = $holders[KeyNormalizer::normalize($id)] ?? null;

            if ($holder !== null) {
                $found[$id] = $holder;
            }
        }

        return $found;
    }

    /**
     * Remove every external id of records of one kind.
     *
     * @param  class-string<Model>  $class
     * @param  array<int, string>  $ids
     */
    public function forget(string $class, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        ExternalIdModel::query()
            ->where('subject_type', self::morphClass($class))
            ->whereIn('subject_id', $ids)
            ->delete();
    }

    /**
     * Refuse the sources whose ids another record of the subject's kind holds.
     *
     * @param  array<string, string>  $wanted
     *
     * @throws ValidationException
     */
    private function refuseHeld(Model $subject, array $wanted): void
    {
        if ($wanted === []) {
            return;
        }

        $held = ExternalIdModel::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', '!=', (string) $subject->getKey())
            ->whereIn('source', array_keys($wanted))
            ->whereIn('external_id', array_values($wanted))
            ->get(['source', 'external_id']);

        $errors = [];

        foreach ($held as $row) {
            $externalId = $wanted[$row->source] ?? null;

            if ($externalId !== null && KeyNormalizer::normalize($externalId) === KeyNormalizer::normalize($row->external_id)) {
                $errors['external_ids.'.$row->source] = self::message($subject, $row->source, $externalId);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private static function held(Model $subject, string $source, string $externalId): ValidationException
    {
        return ValidationException::withMessages([
            'external_ids.'.$source => self::message($subject, $source, $externalId),
        ]);
    }

    private static function message(Model $subject, string $source, string $externalId): string
    {
        $kind = Str::of(class_basename($subject))->beforeLast('Model')->snake(' ')->toString();

        return sprintf('Another %s already has the %s id "%s".', $kind, $source, $externalId);
    }

    /**
     * @param  class-string<Model>|string  $type
     */
    private static function morphClass(string $type): string
    {
        return is_a($type, Model::class, true) ? (new $type)->getMorphClass() : $type;
    }
}
