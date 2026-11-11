<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\PresenceVerifierInterface;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpsertOwnersData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpsertOwnersResult;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnersUpsertedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnersUpsertingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Owner\Services\OwnerWriter;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;
use RefactorCircus\Showroom\Support\BatchPresenceVerifier;
use RefactorCircus\Showroom\Support\KeyNormalizer;
use Throwable;

/**
 * Creates and updates a batch of owners from records in API shape, as an
 * ERP sync or an import sends them, at little cost when most are unchanged.
 *
 * 1. Each record is matched to a stored owner, by code or by its external
 *    id in a source, and the matched owners are loaded together, with what
 *    the comparison needs. The owner types the new records name, and the
 *    parents every record names, are loaded once for the batch.
 * 2. Each record is validated against CreateOwnerData's rules or
 *    UpdateOwnerData's, with the batch's `exists` and `unique` lookups read
 *    up front (BatchPresenceVerifier). A matched record may not change the
 *    owner's code or type. Codes, and the external ids records are matched
 *    by, are claimed in batch order: of two records sending one, the first
 *    keeps it, whatever becomes of it, and the second fails.
 * 3. A matched record whose sent fields equal the stored owner's is
 *    unchanged: nothing is written or announced for it, but its external ids
 *    are stamped synced, every unchanged record's in one update once the
 *    writes are done (ExternalIds::touch()).
 * 4. The rest are written in record order, each in its own transaction,
 *    through OwnerWriter, with the events CreateOwnerAction and
 *    UpdateOwnerAction fire. A record may name as its parent an owner an
 *    earlier record created; a move is seen by every later record, with the
 *    new path of the moved owner and everything beneath it.
 * 5. The products beneath every owner that moved, was enabled or disabled,
 *    or changed its flags get one index sync for the whole batch
 *    (ProductIndex::deferring()).
 *
 * A record that fails — invalid, refused by a placement rule or `assign`,
 * or by the database — is reported with its index, its code and its errors;
 * its neighbours are still written. Callers authorize the batch's creates
 * and updates; only a change of flags is checked per record, as the single
 * actions do. A change made between the batch's reads and its writes is
 * overwritten: the last write wins.
 */
final class UpsertOwnersAction
{
    /**
     * What a matched owner is loaded with: what the comparison and a move need.
     *
     * @var list<string>
     */
    private const array MATCHED_WITH = ['type.parentTypes', 'externalIds', 'flags'];

    /**
     * The records counted so far, by outcome.
     *
     * @var array{created: int, updated: int, unchanged: int, skipped: int}
     */
    private array $counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];

    /**
     * The records refused so far, by index.
     *
     * @var array<int, array{index: int, code: string|null, errors: array<string, list<string>>}>
     */
    private array $failures = [];

    /**
     * Every owner the batch read or created, by id: one instance per owner,
     * so what a record writes to one is what every later record sees.
     *
     * @var array<string, OwnerModel>
     */
    private array $owners = [];

    /**
     * The same owners, by normalized code.
     *
     * @var array<string, OwnerModel>
     */
    private array $byCode = [];

    /**
     * The owner types new records name, with their parent types, by
     * normalized code.
     *
     * @var array<string, OwnerTypeModel>
     */
    private array $types = [];

    /**
     * The owners whose products show something new once written: they
     * moved, were enabled or disabled, or their flags changed. By id.
     *
     * @var array<string, OwnerModel>
     */
    private array $reindex = [];

    /**
     * The most values one `whereIn` lookup sends: `showroom.impex.chunk`.
     *
     * @var positive-int
     */
    private int $chunk = 500;

    public function __construct(
        private readonly ProductIndex $index,
        private readonly OwnerWriter $writer,
        private readonly ExternalIds $externalIds,
        private readonly Config $config,
    ) {}

    /**
     * Records that fail are reported in the result, not thrown.
     */
    public function execute(UpsertOwnersData $data): UpsertOwnersResult
    {
        OwnersUpsertingActionEvent::dispatch($data);

        $result = $this->perform($data);

        OwnersUpsertedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(UpsertOwnersData $data): UpsertOwnersResult
    {
        $this->counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];
        $this->failures = [];
        $this->owners = [];
        $this->byCode = [];
        $this->types = [];
        $this->reindex = [];

        $chunk = $this->config->get('showroom.impex.chunk', 500);
        $this->chunk = is_numeric($chunk) ? max(1, (int) $chunk) : 500;

        // One index sync for the batch, queued as the deferral ends. What was
        // written before a failure is still queued.
        return $this->index->deferring(function () use ($data): UpsertOwnersResult {
            try {
                return $this->writeBatch($data);
            } finally {
                $this->queueReindex();
            }
        });
    }

    /**
     * Each record is written in its own transaction, so one record's
     * failure never rolls back its neighbours.
     */
    private function writeBatch(UpsertOwnersData $data): UpsertOwnersResult
    {
        /** @var array<int, array<string, mixed>> $records */
        $records = [];

        foreach ($data->records as $index => $record) {
            if (! is_array($record) || ($record !== [] && array_is_list($record))) {
                $this->fail($index, null, ['record' => ['The record must be an object.']]);

                continue;
            }

            $records[$index] = $record;
        }

        $matched = $this->match($data, $records);
        $this->loadContext($records, $matched);

        [$writes, $unchanged] = $this->preview($data, $records, $matched);

        foreach ($writes as $index => $write) {
            if ($write instanceof CreateOwnerData) {
                $this->create($index, $records[$index], $write);
            } else {
                $this->update($index, $records[$index], $write[0], $write[1]);
            }
        }

        if ($unchanged !== []) {
            DB::transaction(fn () => $this->externalIds->touch($unchanged, OwnerModel::class));
        }

        ksort($this->failures);

        return new UpsertOwnersResult(
            created: $this->counts['created'],
            updated: $this->counts['updated'],
            unchanged: $this->counts['unchanged'],
            skipped: $this->counts['skipped'],
            failures: array_values($this->failures),
        );
    }

    /**
     * The stored owner each record matches, by index, loaded together with
     * what the comparison needs. Codes and external ids match under
     * KeyNormalizer, as the database compares them.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, OwnerModel>
     */
    private function match(UpsertOwnersData $data, array $records): array
    {
        $keys = [];

        foreach ($records as $index => $record) {
            $key = $data->matchesExternalIds() ? self::sentExternalId($record, $data->match) : self::sentString($record, 'code');

            if ($key !== null) {
                $keys[$index] = $key;
            }
        }

        if ($keys === []) {
            return [];
        }

        $distinct = array_values(array_unique($keys));
        /** @var array<string, OwnerModel> $found */
        $found = [];

        if ($data->matchesExternalIds()) {
            /** @var array<string, string> $holders */
            $holders = [];

            foreach (array_chunk($distinct, $this->chunk) as $chunk) {
                foreach ($this->externalIds->find(OwnerModel::class, $data->match, $chunk) as $externalId => $holder) {
                    $holders[(string) $externalId] = $holder;
                }
            }

            $owners = $this->loadOwners('id', array_values(array_unique($holders)), self::MATCHED_WITH);

            foreach ($holders as $externalId => $holder) {
                if (isset($owners[$holder])) {
                    $found[KeyNormalizer::normalize((string) $externalId)] = $owners[$holder];
                }
            }
        } else {
            foreach ($this->loadOwners('code', $distinct, self::MATCHED_WITH) as $owner) {
                $found[KeyNormalizer::normalize($owner->code)] = $owner;
            }
        }

        $matched = [];

        foreach ($keys as $index => $key) {
            $owner = $found[KeyNormalizer::normalize($key)] ?? null;

            if ($owner !== null) {
                $matched[$index] = $owner;
            }
        }

        return $matched;
    }

    /**
     * Read once for the batch what the writes need beside the matched
     * owners: the stored parents the records name, with their type, and the
     * types of the new records, with their parent types.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int, OwnerModel>  $matched
     */
    private function loadContext(array $records, array $matched): void
    {
        $parents = [];
        $types = [];

        foreach ($records as $index => $record) {
            $parent = self::sentString($record, 'parent');

            if ($parent !== null && ! isset($this->byCode[KeyNormalizer::normalize($parent)])) {
                $parents[KeyNormalizer::normalize($parent)] = $parent;
            }

            $type = self::sentString($record, 'type');

            if (! isset($matched[$index]) && $type !== null) {
                $types[KeyNormalizer::normalize($type)] = $type;
            }
        }

        $this->loadOwners('code', array_values($parents), ['type']);

        foreach (array_chunk(array_values($types), $this->chunk) as $chunk) {
            foreach (OwnerTypeModel::query()->with('parentTypes')->whereIn('code', $chunk)->get() as $type) {
                $this->types[KeyNormalizer::normalize($type->code)] = $type;
            }
        }
    }

    /**
     * Validate each record the mode writes, and set aside the matched
     * records that change nothing: the writes, by index, and the ids of the
     * unchanged owners.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int, OwnerModel>  $matched
     * @return array{0: array<int, CreateOwnerData|array{0: OwnerModel, 1: UpdateOwnerData}>, 1: list<string>}
     */
    private function preview(UpsertOwnersData $data, array $records, array $matched): array
    {
        $presence = $this->presence($records, $matched);
        // One set of rules each: the flags rule reads each code once for the batch.
        $createRules = CreateOwnerData::rules();
        $updateRules = UpdateOwnerData::rules();

        $writes = [];
        $unchanged = [];
        /** @var array<string, true> $claimed */
        $claimed = [];
        // The codes earlier records create, by normalized code.
        /** @var array<string, true> $creates */
        $creates = [];

        foreach ($records as $index => $record) {
            $owner = $matched[$index] ?? null;

            try {
                $this->claim($data, $record, $owner, $claimed);

                if ($owner === null ? ! $data->mode->creates() : ! $data->mode->updates()) {
                    $this->counts['skipped']++;

                    continue;
                }

                if ($owner === null) {
                    $create = CreateOwnerData::fromValidated($this->validated($record, $createRules, $presence, $creates));
                    $writes[$index] = $create;
                    $creates[KeyNormalizer::normalize($create->code)] = true;

                    continue;
                }

                $update = $this->validateMatched($record, $owner, $updateRules, $presence, $creates);

                if ($this->unchanged($owner, $update)) {
                    $this->counts['unchanged']++;
                    $unchanged[] = $owner->id;
                } else {
                    $writes[$index] = [$owner, $update];
                }
            } catch (ValidationException $exception) {
                $this->failWith($index, $record, $owner, $exception);
            }
        }

        return [$writes, $unchanged];
    }

    /**
     * Claim the record's code and, matching by a source, its id there, in
     * batch order. The first record sending one keeps it, whatever becomes
     * of that record; a later one fails at `code` as a duplicate.
     *
     * @param  array<string, mixed>  $record
     * @param  array<string, true>  $claimed
     *
     * @throws ValidationException
     */
    private function claim(UpsertOwnersData $data, array $record, ?OwnerModel $owner, array &$claimed): void
    {
        /** @var array<string, string> $keys */
        $keys = [];
        $code = self::sentString($record, 'code') ?? $owner?->code;

        if ($code !== null) {
            $keys['code:'.KeyNormalizer::normalize($code)] = sprintf('Another record of the batch has the code "%s".', $code);
        }

        $externalId = $data->matchesExternalIds() ? self::sentExternalId($record, $data->match) : null;

        if ($externalId !== null) {
            $keys['id:'.KeyNormalizer::normalize($externalId)] = sprintf('Another record of the batch has the %s id "%s".', $data->match, $externalId);
        }

        $duplicates = array_values(array_intersect_key($keys, $claimed));
        $claimed += array_fill_keys(array_keys($keys), true);

        if ($duplicates !== []) {
            throw ValidationException::withMessages(['code' => $duplicates]);
        }
    }

    /**
     * A matched record, validated as UpdateOwnerData. Its code and type name
     * the owner, and are taken off once they agree with it: neither changes.
     *
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $rules
     * @param  array<string, true>  $creates
     *
     * @throws ValidationException
     */
    private function validateMatched(array $record, OwnerModel $owner, array $rules, BatchPresenceVerifier $presence, array $creates): UpdateOwnerData
    {
        foreach (['code' => $owner->code, 'type' => $owner->type->code] as $key => $stored) {
            if (($record[$key] ?? null) !== null && ! self::same($record[$key], $stored)) {
                throw ValidationException::withMessages([
                    $key => sprintf('The %s of owner "%s" cannot be changed.', $key, $owner->code),
                ]);
            }

            unset($record[$key]);
        }

        return UpdateOwnerData::fromValidated($this->validated($record, $rules, $presence, $creates));
    }

    /**
     * A record validated against a set of rules. A parent that an earlier
     * record of the batch creates is not stored yet, so it skips `exists`:
     * it is looked up when the record is written, and refused there if its
     * own record failed.
     *
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $rules
     * @param  array<string, true>  $creates
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validated(array $record, array $rules, BatchPresenceVerifier $presence, array $creates): array
    {
        $parent = self::sentString($record, 'parent');
        $createdEarlier = $parent !== null && isset($creates[KeyNormalizer::normalize($parent)]);

        if ($createdEarlier) {
            unset($record['parent']);
        }

        $validator = Validator::make($record, $rules);
        $validator->setPresenceVerifier($presence);

        /** @var array<string, mixed> $validated */
        $validated = $validator->validate();

        if ($createdEarlier) {
            $validated['parent'] = $parent;
        }

        return $validated;
    }

    /**
     * Whether every field the record sends equals what the owner holds,
     * compared as OwnerWriter would store it. A field left out is not
     * compared; a null parent is the root.
     */
    private function unchanged(OwnerModel $owner, UpdateOwnerData $data): bool
    {
        if (! $data->labels instanceof Missing && self::canonical($data->labels ?? []) !== self::canonical($owner->labels ?? [])) {
            return false;
        }

        if (is_bool($data->enabled) && $data->enabled !== $owner->enabled) {
            return false;
        }

        if (! $data->parent instanceof Missing) {
            // A parent not stored yet, which an earlier record creates, is never the current one.
            $parentId = $data->parent === null ? null : ($this->byCode[KeyNormalizer::normalize($data->parent)]->id ?? '');

            if ($parentId !== $owner->parent_id) {
                return false;
            }
        }

        if (! $data->externalIds instanceof Missing) {
            $stored = ExternalIds::present($owner->externalIds);

            foreach ($data->externalIds as $source => $externalId) {
                if (($stored[(string) $source] ?? null) !== $externalId) {
                    return false;
                }
            }
        }

        return $data->flags instanceof Missing || self::codes($data->flags) === self::codes($owner->flags->pluck('code')->all());
    }

    /**
     * Create one owner in its own transaction, with CreateOwnerAction's
     * events. Once it commits, later records may name it as their parent.
     *
     * @param  array<string, mixed>  $record
     */
    private function create(int $index, array $record, CreateOwnerData $data): void
    {
        try {
            $type = $this->types[KeyNormalizer::normalize($data->type)] ?? throw self::unknown('type');
            $parent = $data->parent === null ? null : ($this->byCode[KeyNormalizer::normalize($data->parent)] ?? throw self::unknown('parent'));

            // Named as stored, as the writer checks them.
            $data = new CreateOwnerData(
                code: $data->code,
                type: $type->code,
                parent: $parent?->code,
                labels: $data->labels,
                externalIds: $data->externalIds,
                flags: $data->flags,
                enabled: $data->enabled,
            );

            OwnerCreatingActionEvent::dispatch($data);

            $owner = DB::transaction(fn (): OwnerModel => $this->writer->create($data, $type, $parent));
        } catch (ValidationException|AuthorizationException|QueryException $exception) {
            $this->failWith($index, $record, null, $exception);

            return;
        }

        $this->remember($owner);
        $this->counts['created']++;

        OwnerCreatedActionEvent::dispatch($owner);
    }

    /**
     * Update one owner in its own transaction, with UpdateOwnerAction's
     * events. A rolled-back write leaves the owner as stored for the records
     * after it; a committed move carries the owners beneath it along.
     *
     * @param  array<string, mixed>  $record
     */
    private function update(int $index, array $record, OwnerModel $owner, UpdateOwnerData $data): void
    {
        $attributes = $owner->getAttributes();
        $hadParent = $owner->relationLoaded('parent');
        $previousParent = $hadParent ? $owner->getRelation('parent') : null;
        $oldPath = $owner->path;
        $oldDepth = $owner->depth;

        try {
            $parent = null;

            if (is_string($data->parent)) {
                $parent = $this->byCode[KeyNormalizer::normalize($data->parent)] ?? throw self::unknown('parent');

                // Named as stored, as the writer checks it.
                $data = new UpdateOwnerData(parent: $parent->code, labels: $data->labels, externalIds: $data->externalIds, flags: $data->flags, enabled: $data->enabled);
            }

            OwnerUpdatingActionEvent::dispatch($owner, $data);

            $reindex = DB::transaction(fn (): bool => $this->writer->update($owner, $data, $parent, $owner->type));
        } catch (Throwable $exception) {
            // Rolled back: the owner is as stored again.
            $owner->setRawAttributes($attributes, true);

            if ($hadParent) {
                $owner->setRelation('parent', $previousParent);
            } else {
                $owner->unsetRelation('parent');
            }

            if (! $exception instanceof ValidationException && ! $exception instanceof AuthorizationException && ! $exception instanceof QueryException) {
                throw $exception;
            }

            $this->failWith($index, $record, $owner, $exception);

            return;
        }

        if ($owner->path !== $oldPath) {
            $this->carryBeneath($owner, $oldPath, $oldDepth);
        }

        if ($reindex) {
            $this->reindex[$owner->id] = $owner;
        }

        $this->counts['updated']++;

        OwnerUpdatedActionEvent::dispatch($owner);
    }

    /**
     * Give the owners the batch holds beneath a moved owner their new path
     * and depth, as the move wrote them, so later records place and check
     * against where they are now.
     */
    private function carryBeneath(OwnerModel $moved, string $oldPath, int $oldDepth): void
    {
        foreach ($this->owners as $owner) {
            if ($owner !== $moved && str_starts_with($owner->path, $oldPath)) {
                $owner->path = $moved->path.substr($owner->path, strlen($oldPath));
                $owner->depth = $owner->depth - $oldDepth + $moved->depth;
                $owner->syncOriginalAttributes(['path', 'depth']);
            }
        }
    }

    /**
     * Queue the products beneath every owner that moved, was enabled or
     * disabled, or changed its flags, into the batch's one deferred sync. An owner beneath another
     * one listed is covered by it.
     */
    private function queueReindex(): void
    {
        $owners = $this->reindex;
        $this->reindex = [];

        foreach ($owners as $owner) {
            foreach ($owners as $other) {
                if ($other !== $owner && str_starts_with($owner->path, $other->path)) {
                    continue 2;
                }
            }

            $this->index->queue($this->index->productIdsOwnedWithin($owner));
        }
    }

    /**
     * Read owners by a column, a chunk of values at a time, into the batch's
     * map. An owner the batch already holds is kept as it is.
     *
     * @param  list<string>  $values
     * @param  list<string>  $with
     * @return array<string, OwnerModel> By id.
     */
    private function loadOwners(string $column, array $values, array $with): array
    {
        $loaded = [];

        foreach (array_chunk($values, $this->chunk) as $chunk) {
            foreach (OwnerModel::query()->with($with)->whereIn($column, $chunk)->get() as $owner) {
                $owner = $this->remember($owner);
                $loaded[$owner->id] = $owner;
            }
        }

        return $loaded;
    }

    private function remember(OwnerModel $owner): OwnerModel
    {
        $owner = $this->owners[$owner->id] ??= $owner;
        $this->byCode[KeyNormalizer::normalize($owner->code)] = $owner;

        return $owner;
    }

    /**
     * A verifier that answers the batch's `exists` and `unique` rules from
     * one query per column: the codes of the new records and of every parent
     * named, and the types of the new records.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int, OwnerModel>  $matched
     */
    private function presence(array $records, array $matched): BatchPresenceVerifier
    {
        /** @var PresenceVerifierInterface $verifier */
        $verifier = app('validation.presence');
        $presence = new BatchPresenceVerifier($verifier);
        $new = array_diff_key($records, $matched);
        $sent = fn (array $from, string $key): array => array_values(array_filter(array_map(
            fn (array $record): ?string => self::sentString($record, $key),
            $from,
        )));

        foreach (array_chunk(array_values(array_unique([...$sent($new, 'code'), ...$sent($records, 'parent')])), $this->chunk) as $chunk) {
            $presence->prime(OwnerModel::class, 'code', $chunk);
        }

        foreach (array_chunk(array_values(array_unique($sent($new, 'type'))), $this->chunk) as $chunk) {
            $presence->prime(OwnerTypeModel::class, 'code', $chunk);
        }

        return $presence;
    }

    /**
     * A database error is reported without its SQL; one other than a unique
     * key taken mid-write goes to the exception handler as well.
     *
     * @param  array<string, mixed>  $record
     */
    private function failWith(int $index, array $record, ?OwnerModel $owner, ValidationException|AuthorizationException|QueryException $exception): void
    {
        $code = self::sentString($record, 'code') ?? $owner?->code;

        if ($exception instanceof QueryException && ! $exception instanceof UniqueConstraintViolationException) {
            report($exception);
        }

        /** @var array<string, list<string>> $errors */
        $errors = match (true) {
            $exception instanceof ValidationException => $exception->errors(),
            // Only a change of flags is authorized per record.
            $exception instanceof AuthorizationException => ['flags' => [$exception->getMessage()]],
            // An update keeps its code, so only a new owner's can be taken under it.
            $exception instanceof UniqueConstraintViolationException && $owner === null => ['code' => [sprintf('Another owner took the code "%s" while this record was written.', $code)]],
            $exception instanceof UniqueConstraintViolationException => ['record' => ['Another owner took one of this record\'s unique keys while it was written.']],
            default => ['record' => ['The record could not be written to the database.']],
        };

        $this->fail($index, $code, $errors);
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function fail(int $index, ?string $code, array $errors): void
    {
        $this->failures[$index] = ['index' => $index, 'code' => $code, 'errors' => $errors];
    }

    /**
     * The refusal of a parent or type that is not stored, as the `exists`
     * rule words it: one named before its own record, or whose record failed.
     */
    private static function unknown(string $key): ValidationException
    {
        $message = trans('validation.exists', ['attribute' => $key]);

        return ValidationException::withMessages([$key => is_string($message) ? $message : sprintf('The selected %s is invalid.', $key)]);
    }

    /**
     * A non-empty string the record sends at a key, if any.
     *
     * @param  array<string, mixed>  $record
     */
    private static function sentString(array $record, string $key): ?string
    {
        $value = $record[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * The id the record sends for a source, if any.
     *
     * @param  array<string, mixed>  $record
     */
    private static function sentExternalId(array $record, string $source): ?string
    {
        $externalIds = $record['external_ids'] ?? null;
        $externalId = is_array($externalIds) ? ($externalIds[$source] ?? null) : null;

        return is_string($externalId) && $externalId !== '' ? $externalId : null;
    }

    /**
     * Whether a sent code names the stored one.
     */
    private static function same(mixed $sent, string $stored): bool
    {
        return is_string($sent) && KeyNormalizer::normalize($sent) === KeyNormalizer::normalize($stored);
    }

    /**
     * Codes as a set, to compare.
     *
     * @param  array<array-key, mixed>  $codes
     * @return list<string>
     */
    private static function codes(array $codes): array
    {
        $normalized = array_values(array_unique(array_map(fn (mixed $code): string => KeyNormalizer::normalize((string) $code), $codes)));
        sort($normalized, SORT_STRING);

        return $normalized;
    }

    /**
     * A map with its keys sorted, so two stored forms compare equal whatever
     * order they were written in.
     *
     * @param  array<array-key, mixed>  $map
     * @return array<array-key, mixed>
     */
    private static function canonical(array $map): array
    {
        ksort($map);

        return $map;
    }
}
