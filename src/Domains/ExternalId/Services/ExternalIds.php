<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ExternalId\Services;

use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Support\KeyNormalizer;

/**
 * Products' and owners' ids in other systems, as a `{system: id}` map.
 *
 * An id is unique per kind of record and system: a product and an owner may
 * share one, two products may not. The database's unique index enforces it
 * under the column's collation (case- and accent-insensitive by default);
 * what this service matches in memory it matches the same way, through
 * KeyNormalizer.
 *
 * The rows are polymorphic, so no foreign key removes them with their
 * record; the Actions that delete products and owners call forget().
 */
final class ExternalIds
{
    /**
     * A system name: lowercase letters, digits, dashes and underscores.
     */
    public const string SYSTEM_PATTERN = '/^[a-z0-9_-]{1,50}$/D';

    /**
     * The `external_ids` input of a create or an update: a `{system: id}`
     * map. On update only the systems sent change, and null removes one.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'external_ids' => ['sometimes', 'array', self::systems(...)],
            'external_ids.*' => ['nullable', 'string', 'max:191'],
        ];
    }

    /**
     * The `external` filter of a listing: `external[{system}]={id}`, every
     * pair matched.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function filterRules(): array
    {
        return [
            'external' => ['sometimes', 'array', self::systems(...)],
            'external.*' => ['required', 'string', 'max:191'],
        ];
    }

    /**
     * Refuse a list, or a key that is not a system name.
     */
    private static function systems(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        if ($value !== [] && array_is_list($value)) {
            $fail(sprintf('The %s field must be a {system: id} map.', $attribute));

            return;
        }

        foreach (array_keys($value) as $system) {
            if (preg_match(self::SYSTEM_PATTERN, (string) $system) !== 1) {
                $fail(sprintf('The %s system "%s" must be 1 to 50 lowercase letters, digits, dashes or underscores.', $attribute, $system));
            }
        }
    }

    /**
     * The `{system: id}` map of a record's external id rows, by system.
     *
     * @param  iterable<int, ExternalIdModel>  $rows
     * @return array<string, string>
     */
    public static function present(iterable $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $map[$row->system] = $row->external_id;
        }

        ksort($map, SORT_STRING);

        return $map;
    }

    /**
     * Apply a `{system: id}` map to a record: each system sent is set, null
     * removes it, and systems not sent are kept. Call it inside the write's
     * transaction, so a refusal leaves nothing written.
     *
     * @param  array<array-key, string|null>  $map
     *
     * @throws ValidationException When another record of the same kind holds a (system, id), at `external_ids.{system}`.
     */
    public function write(Model $subject, array $map): void
    {
        if ($map === []) {
            return;
        }

        $type = $subject->getMorphClass();
        $subjectId = (string) $subject->getKey();

        /** @var array<string, string> $wanted */
        $wanted = [];

        foreach ($map as $system => $externalId) {
            if ($externalId !== null) {
                $wanted[(string) $system] = $externalId;
            }
        }

        $this->refuseHeld($subject, $wanted);

        /** @var array<string, ExternalIdModel> $current */
        $current = ExternalIdModel::query()
            ->where('subject_type', $type)
            ->where('subject_id', $subjectId)
            ->whereIn('system', array_map('strval', array_keys($map)))
            ->get()
            ->keyBy('system')
            ->all();

        foreach ($map as $system => $externalId) {
            $system = (string) $system;
            $row = $current[$system] ?? null;

            if ($externalId === null) {
                $row?->delete();

                continue;
            }

            if ($row?->external_id === $externalId) {
                continue;
            }

            $row ??= new ExternalIdModel(['subject_type' => $type, 'subject_id' => $subjectId, 'system' => $system]);
            $row->external_id = $externalId;

            try {
                $row->save();
            } catch (UniqueConstraintViolationException) {
                // Taken between the check and the write.
                throw self::held($subject, $system, $externalId);
            }
        }
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
     * The records of one kind holding ids in a system: each id asked for that
     * is held, as sent, to its holder's key. Ids that differ only by case or
     * accent match, as the database compares them.
     *
     * @param  class-string<Model>|string  $type  A model class, or the morph alias its rows are stored under.
     * @param  array<int, string>  $ids
     * @return array<string, string>
     */
    public function find(string $type, string $system, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $holders = [];

        foreach (ExternalIdModel::query()
            ->where('subject_type', self::morphClass($type))
            ->where('system', $system)
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
     * Refuse the systems whose ids another record of the subject's kind holds.
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
            ->whereIn('system', array_keys($wanted))
            ->whereIn('external_id', array_values($wanted))
            ->get(['system', 'external_id']);

        $errors = [];

        foreach ($held as $row) {
            $externalId = $wanted[$row->system] ?? null;

            if ($externalId !== null && KeyNormalizer::normalize($externalId) === KeyNormalizer::normalize($row->external_id)) {
                $errors['external_ids.'.$row->system] = self::message($subject, $row->system, $externalId);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private static function held(Model $subject, string $system, string $externalId): ValidationException
    {
        return ValidationException::withMessages([
            'external_ids.'.$system => self::message($subject, $system, $externalId),
        ]);
    }

    private static function message(Model $subject, string $system, string $externalId): string
    {
        $kind = Str::of(class_basename($subject))->beforeLast('Model')->snake(' ')->toString();

        return sprintf('Another %s already has the %s id "%s".', $kind, $system, $externalId);
    }

    /**
     * @param  class-string<Model>|string  $type
     */
    private static function morphClass(string $type): string
    {
        return is_a($type, Model::class, true) ? (new $type)->getMorphClass() : $type;
    }
}
