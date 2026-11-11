<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeOptionData;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpdateAttributeOptionData;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpsertAttributeOptionsData;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpsertAttributeOptionsResult;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionCreatedEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionCreatingEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionSavedEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionSavingEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionsUpsertedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionsUpsertingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Exceptions\AttributeHasNoOptionsException;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;
use RefactorCircus\Showroom\Support\KeyNormalizer;

/**
 * Creates and updates a batch of attribute options from records
 * (`attribute`, `code`, `labels`, `sort_order`), as a catalog sync or an
 * import sends them, at little cost when most are unchanged. One batch may
 * fill the options of many attributes.
 *
 * 1. The attributes the records name are loaded once for the batch, and
 *    each record is matched to a stored option by its attribute and its
 *    code, every match read together. Codes compare under KeyNormalizer, as
 *    the database compares them; option codes are lowercase, so a code in
 *    another case is refused at `code` rather than matched.
 * 2. A record whose attribute is unknown, or of a type that takes no
 *    options, fails at `attribute`. Each other record is validated against
 *    CreateAttributeOptionData's rules or UpdateAttributeOptionData's. An
 *    (attribute, code) is claimed in batch order: of two records sending
 *    one, the first keeps it, whatever becomes of it, and the second fails
 *    at `code`.
 * 3. A matched record whose sent fields equal the stored option's is
 *    unchanged: nothing is written or announced for it. A field left out
 *    keeps its stored value.
 * 4. New options are inserted together, a few hundred rows per statement,
 *    in one transaction; if that fails, they are inserted one by one, so
 *    whatever broke it fails only its own record. Each fires the events
 *    CreateAttributeOptionAction fires: its starting event before the
 *    insert, its finished event after, and the Eloquent `saving`,
 *    `creating`, `created` and `saved` events around it, dispatched by the
 *    batch since a bulk insert fires none (they cannot halt the insert).
 * 5. Each changed option is saved in its own transaction, with the events
 *    UpdateAttributeOptionAction fires.
 *
 * Product values store option codes, not labels, and the search document
 * holds neither, so no product is re-indexed: as with the single update.
 *
 * A record that fails is reported with its index, its attribute, its code
 * and its errors; its neighbours are still written. Callers authorize the
 * batch's creates and updates; nothing is checked per record. A change made
 * between the batch's reads and its writes is overwritten: the last write
 * wins.
 */
final class UpsertAttributeOptionsAction
{
    /**
     * Rows per insert statement, under every driver's placeholder limit.
     */
    private const int ROWS_PER_INSERT = 200;

    /**
     * The records counted so far, by outcome.
     *
     * @var array{created: int, updated: int, unchanged: int, skipped: int}
     */
    private array $counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];

    /**
     * The records refused so far, by index.
     *
     * @var array<int, array{index: int, attribute: string|null, code: string|null, errors: array<string, list<string>>}>
     */
    private array $failures = [];

    /**
     * The most values one `whereIn` lookup sends: `showroom.impex.chunk`.
     *
     * @var positive-int
     */
    private int $chunk = 500;

    public function __construct(
        private readonly Config $config,
    ) {}

    /**
     * Records that fail are reported in the result, not thrown.
     */
    public function execute(UpsertAttributeOptionsData $data): UpsertAttributeOptionsResult
    {
        AttributeOptionsUpsertingActionEvent::dispatch($data);

        $result = $this->perform($data);

        AttributeOptionsUpsertedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * Each write runs in its own transaction, so one record's failure never
     * rolls back its neighbours.
     */
    private function perform(UpsertAttributeOptionsData $data): UpsertAttributeOptionsResult
    {
        $this->counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];
        $this->failures = [];

        $chunk = $this->config->get('showroom.impex.chunk', 500);
        $this->chunk = is_numeric($chunk) ? max(1, (int) $chunk) : 500;

        /** @var array<int, array<string, mixed>> $records */
        $records = [];

        foreach ($data->records as $index => $record) {
            if (! is_array($record) || ($record !== [] && array_is_list($record))) {
                $this->fail($index, null, null, ['record' => ['The record must be an object.']]);

                continue;
            }

            $records[$index] = $record;
        }

        $attributes = $this->loadAttributes($records);
        $matched = $this->match($records, $attributes);

        [$creates, $updates] = $this->preview($data, $records, $attributes, $matched);

        $this->createAll($creates, $records);
        $this->updateAll($updates, $records);

        ksort($this->failures);

        return new UpsertAttributeOptionsResult(
            created: $this->counts['created'],
            updated: $this->counts['updated'],
            unchanged: $this->counts['unchanged'],
            skipped: $this->counts['skipped'],
            failures: array_values($this->failures),
        );
    }

    /**
     * The attributes the records name, by normalized code, a chunk of codes
     * per query.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array<string, AttributeModel>
     */
    private function loadAttributes(array $records): array
    {
        $codes = [];

        foreach ($records as $record) {
            $code = self::sentString($record, 'attribute');

            if ($code !== null) {
                $codes[KeyNormalizer::normalize($code)] = $code;
            }
        }

        $attributes = [];

        foreach (array_chunk(array_values($codes), $this->chunk) as $chunk) {
            foreach (AttributeModel::query()->whereIn('code', $chunk)->get() as $attribute) {
                $attributes[KeyNormalizer::normalize($attribute->code)] = $attribute;
            }
        }

        return $attributes;
    }

    /**
     * The stored option each record matches, by index, read together: the
     * options of the batch's attributes whose codes the batch sends, a chunk
     * of each per query.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<string, AttributeModel>  $attributes
     * @return array<int, AttributeOptionModel>
     */
    private function match(array $records, array $attributes): array
    {
        $keys = [];
        $attributeIds = [];
        $codes = [];

        foreach ($records as $index => $record) {
            $attribute = self::attributeOf($record, $attributes);
            $code = self::sentString($record, 'code');

            if ($attribute !== null && $attribute->type->hasOptions() && $code !== null) {
                $keys[$index] = self::key($attribute->id, $code);
                $attributeIds[$attribute->id] = $attribute->id;
                $codes[KeyNormalizer::normalize($code)] = $code;
            }
        }

        if ($keys === []) {
            return [];
        }

        $byId = [];

        foreach ($attributes as $attribute) {
            $byId[$attribute->id] = $attribute;
        }

        /** @var array<string, AttributeOptionModel> $found */
        $found = [];

        foreach (array_chunk(array_values($attributeIds), $this->chunk) as $idChunk) {
            foreach (array_chunk(array_values($codes), $this->chunk) as $codeChunk) {
                $options = AttributeOptionModel::query()->whereIn('attribute_id', $idChunk)->whereIn('code', $codeChunk)->get();

                foreach ($options as $option) {
                    $option->setRelation('attribute', $byId[$option->attribute_id]);
                    $found[self::key($option->attribute_id, $option->code)] = $option;
                }
            }
        }

        $matched = [];

        foreach ($keys as $index => $key) {
            if (isset($found[$key])) {
                $matched[$index] = $found[$key];
            }
        }

        return $matched;
    }

    /**
     * Validate each record the mode writes, and set aside the matched
     * records that change nothing: the creates and the updates, by index.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<string, AttributeModel>  $attributes
     * @param  array<int, AttributeOptionModel>  $matched
     * @return array{0: array<int, array{0: AttributeModel, 1: CreateAttributeOptionData}>, 1: array<int, array{0: AttributeOptionModel, 1: UpdateAttributeOptionData}>}
     */
    private function preview(UpsertAttributeOptionsData $data, array $records, array $attributes, array $matched): array
    {
        $createRules = CreateAttributeOptionData::rules();
        // A matched record's code names the option: it is held to the code
        // rule, then taken off, as it never changes.
        $updateRules = ['code' => $createRules['code']] + UpdateAttributeOptionData::rules();

        $creates = [];
        $updates = [];
        /** @var array<string, true> $claimed */
        $claimed = [];

        foreach ($records as $index => $record) {
            $option = $matched[$index] ?? null;

            try {
                $this->claim($record, $claimed);
                $attribute = self::optionsAttribute($record, $attributes);

                if ($option === null ? ! $data->mode->creates() : ! $data->mode->updates()) {
                    $this->counts['skipped']++;

                    continue;
                }

                if ($option === null) {
                    $creates[$index] = [$attribute, CreateAttributeOptionData::fromValidated(self::validated($record, $createRules))];

                    continue;
                }

                $validated = self::validated($record, $updateRules);
                unset($validated['code']);
                $update = UpdateAttributeOptionData::fromValidated($validated);

                if (self::unchanged($option, $update)) {
                    $this->counts['unchanged']++;
                } else {
                    $updates[$index] = [$option, $update];
                }
            } catch (ValidationException $exception) {
                $this->failWith($index, $record, $exception);
            }
        }

        return [$creates, $updates];
    }

    /**
     * Claim the record's (attribute, code) in batch order. The first record
     * sending one keeps it, whatever becomes of that record; a later one
     * fails at `code` as a duplicate.
     *
     * @param  array<string, mixed>  $record
     * @param  array<string, true>  $claimed
     *
     * @throws ValidationException
     */
    private function claim(array $record, array &$claimed): void
    {
        $attribute = self::sentString($record, 'attribute');
        $code = self::sentString($record, 'code');

        if ($attribute === null || $code === null) {
            return;
        }

        $key = self::key(KeyNormalizer::normalize($attribute), $code);

        if (isset($claimed[$key])) {
            throw ValidationException::withMessages([
                'code' => sprintf('Another record of the batch has the code "%s" for attribute "%s".', $code, $attribute),
            ]);
        }

        $claimed[$key] = true;
    }

    /**
     * Insert the new options together, with CreateAttributeOptionAction's
     * events. When the bulk insert fails, nothing of it is kept, and the
     * options are inserted one by one, each in its own transaction.
     *
     * @param  array<int, array{0: AttributeModel, 1: CreateAttributeOptionData}>  $creates
     * @param  array<int, array<string, mixed>>  $records
     */
    private function createAll(array $creates, array $records): void
    {
        if ($creates === []) {
            return;
        }

        /** @var array<int, AttributeOptionModel> $options */
        $options = [];

        foreach ($creates as $index => [$attribute, $data]) {
            AttributeOptionCreatingActionEvent::dispatch($attribute, $data);

            $option = new AttributeOptionModel([
                'code' => $data->code,
                'labels' => $data->labels ?? [],
                'sort_order' => $data->sortOrder,
            ]);
            $option->attribute()->associate($attribute);

            self::fireModelEvents($option, new AttributeOptionSavingEvent($option), new AttributeOptionCreatingEvent($option));

            $option->setUniqueIds();
            $option->updateTimestamps();
            $options[$index] = $option;
        }

        try {
            DB::transaction(function () use ($options): void {
                foreach (array_chunk(array_map(fn (AttributeOptionModel $option): array => $option->getAttributes(), $options), self::ROWS_PER_INSERT) as $rows) {
                    AttributeOptionModel::query()->insert($rows);
                }
            });
        } catch (QueryException) {
            foreach ($options as $index => $option) {
                try {
                    DB::transaction(fn (): bool => AttributeOptionModel::query()->insert($option->getAttributes()));
                } catch (QueryException $exception) {
                    $this->failWith($index, $records[$index], $exception);

                    continue;
                }

                $this->created($option);
            }

            return;
        }

        foreach ($options as $option) {
            $this->created($option);
        }
    }

    /**
     * Mark an inserted option stored, as Eloquent does after an insert, and
     * announce it.
     */
    private function created(AttributeOptionModel $option): void
    {
        $option->exists = true;
        $option->wasRecentlyCreated = true;

        self::fireModelEvents($option, new AttributeOptionCreatedEvent($option), new AttributeOptionSavedEvent($option));

        $option->syncOriginal();
        $this->counts['created']++;

        AttributeOptionCreatedActionEvent::dispatch($option);
    }

    /**
     * Save each changed option in its own transaction, with
     * UpdateAttributeOptionAction's events. A rolled-back write leaves the
     * option as stored.
     *
     * @param  array<int, array{0: AttributeOptionModel, 1: UpdateAttributeOptionData}>  $updates
     * @param  array<int, array<string, mixed>>  $records
     */
    private function updateAll(array $updates, array $records): void
    {
        foreach ($updates as $index => [$option, $data]) {
            $attributes = $option->getAttributes();

            AttributeOptionUpdatingActionEvent::dispatch($option, $data);

            try {
                DB::transaction(function () use ($option, $data): void {
                    if (! $data->labels instanceof Missing) {
                        $option->labels = $data->labels ?? [];
                    }

                    if (! $data->sortOrder instanceof Missing) {
                        $option->sort_order = $data->sortOrder;
                    }

                    $option->save();
                });
            } catch (QueryException $exception) {
                $option->setRawAttributes($attributes, true);
                $this->failWith($index, $records[$index], $exception);

                continue;
            }

            $this->counts['updated']++;

            AttributeOptionUpdatedActionEvent::dispatch($option);
        }
    }

    /**
     * Whether every field the record sends equals what the option holds, as
     * UpdateAttributeOptionAction would store it. A field left out is not
     * compared; null labels are none.
     */
    private static function unchanged(AttributeOptionModel $option, UpdateAttributeOptionData $data): bool
    {
        if (! $data->labels instanceof Missing && self::canonical($data->labels ?? []) !== self::canonical($option->labels ?? [])) {
            return false;
        }

        return $data->sortOrder instanceof Missing || $data->sortOrder === $option->sort_order;
    }

    /**
     * Dispatch Eloquent's events for a hook the bulk insert passes by: the
     * model's own event class and the `eloquent.{hook}` string event, as
     * Eloquent does, unless model events are muted.
     */
    private static function fireModelEvents(AttributeOptionModel $option, ModelLifecycleEvent ...$events): void
    {
        $dispatcher = AttributeOptionModel::getEventDispatcher();

        if ($dispatcher === null) {
            return;
        }

        foreach ($events as $event) {
            $dispatcher->dispatch($event);
            $dispatcher->dispatch('eloquent.'.$event->hook().': '.$option::class, $option);
        }
    }

    /**
     * The record's attribute, which must exist and take options.
     *
     * @param  array<string, mixed>  $record
     * @param  array<string, AttributeModel>  $attributes
     *
     * @throws ValidationException
     */
    private static function optionsAttribute(array $record, array $attributes): AttributeModel
    {
        if (self::sentString($record, 'attribute') === null) {
            throw self::refuseAttribute('validation.required', 'The attribute field is required.');
        }

        $attribute = self::attributeOf($record, $attributes)
            ?? throw self::refuseAttribute('validation.exists', 'The selected attribute is invalid.');

        if (! $attribute->type->hasOptions()) {
            throw ValidationException::withMessages(['attribute' => AttributeHasNoOptionsException::for($attribute)->getMessage()]);
        }

        return $attribute;
    }

    private static function refuseAttribute(string $key, string $fallback): ValidationException
    {
        $message = trans($key, ['attribute' => 'attribute']);

        return ValidationException::withMessages(['attribute' => is_string($message) ? $message : $fallback]);
    }

    /**
     * The stored attribute the record names, if any.
     *
     * @param  array<string, mixed>  $record
     * @param  array<string, AttributeModel>  $attributes
     */
    private static function attributeOf(array $record, array $attributes): ?AttributeModel
    {
        $code = self::sentString($record, 'attribute');

        return $code === null ? null : ($attributes[KeyNormalizer::normalize($code)] ?? null);
    }

    /**
     * A record validated against a set of rules: only the keys they name.
     *
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private static function validated(array $record, array $rules): array
    {
        /** @var array<string, mixed> $validated */
        $validated = Validator::make($record, $rules)->validate();

        return $validated;
    }

    /**
     * A database error is reported without its SQL; one other than a unique
     * key taken mid-write goes to the exception handler as well.
     *
     * @param  array<string, mixed>  $record
     */
    private function failWith(int $index, array $record, ValidationException|QueryException $exception): void
    {
        $attribute = self::sentString($record, 'attribute');
        $code = self::sentString($record, 'code');

        if ($exception instanceof QueryException && ! $exception instanceof UniqueConstraintViolationException) {
            report($exception);
        }

        /** @var array<string, list<string>> $errors */
        $errors = match (true) {
            $exception instanceof ValidationException => $exception->errors(),
            // Only a new option's code can be taken under it: an update keeps its code.
            $exception instanceof UniqueConstraintViolationException => ['code' => [sprintf('Another option of attribute "%s" took the code "%s" while this record was written.', $attribute, $code)]],
            default => ['record' => ['The record could not be written to the database.']],
        };

        $this->fail($index, $attribute, $code, $errors);
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function fail(int $index, ?string $attribute, ?string $code, array $errors): void
    {
        $this->failures[$index] = ['index' => $index, 'attribute' => $attribute, 'code' => $code, 'errors' => $errors];
    }

    /**
     * The key an (attribute, code) is matched and claimed by.
     */
    private static function key(string $attribute, string $code): string
    {
        return $attribute."\0".KeyNormalizer::normalize($code);
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
