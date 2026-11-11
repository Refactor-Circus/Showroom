<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\PresenceVerifierInterface;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Attribute\Services\Values;
use RefactorCircus\Showroom\Domains\Attribute\Services\ValueValidator;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Flag\Services\Flags;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;
use RefactorCircus\Showroom\Domains\Product\Data\UpdateProductData;
use RefactorCircus\Showroom\Domains\Product\Data\UpsertProductsData;
use RefactorCircus\Showroom\Domains\Product\Data\UpsertProductsResult;
use RefactorCircus\Showroom\Domains\Product\Events\ProductsUpsertedActionEvent;
use RefactorCircus\Showroom\Domains\Product\Events\ProductsUpsertingActionEvent;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Product\Services\BulkProductWriter;
use RefactorCircus\Showroom\Domains\Product\Services\ProductChangeCapture;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;
use RefactorCircus\Showroom\Support\BatchPresenceVerifier;
use RefactorCircus\Showroom\Support\KeyNormalizer;
use Throwable;

/**
 * Creates and updates a batch of products from records in API shape, as an
 * ERP sync or an import sends them, at little cost when most are unchanged.
 *
 * 1. Each record is matched to a stored product, by identifier or by its
 *    external id in a source, and the matched products are loaded together.
 * 2. Each record is validated against CreateProductData's rules or
 *    UpdateProductData's, and a change to its flags must pass `assign`.
 *    Identifiers and external ids are claimed in batch order: of two
 *    records sending one, the first persists.
 * 3. A matched record whose sent fields equal the stored product's is
 *    unchanged: nothing is written, versioned, queued or announced for it,
 *    but its external ids are stamped synced, every unchanged record's in
 *    one update for the batch (ExternalIds::touch()).
 * 4. The rest are written, with one index sync for the whole batch: new
 *    simple products in bulk (BulkProductWriter), every other record through
 *    CreateProductAction or UpdateProductAction, each on its own. A new
 *    record taking an external id an earlier update releases is written
 *    after the updates.
 *
 * A record that fails — invalid, refused by a rule of the write, or by the
 * database — is
 * reported with its index, its identifier and its errors; its neighbours are
 * still written. When the bulk write itself fails, its records are written
 * one by one instead.
 */
final class UpsertProductsAction
{
    /**
     * What the preview compares a matched record with.
     *
     * @var list<string>
     */
    private const array MATCHED_WITH = ['family', 'owner', 'parent', 'categories', 'associations.type', 'associations.target', 'externalIds', 'flags'];

    /**
     * The records counted so far, by outcome.
     *
     * @var array{created: int, updated: int, unchanged: int, skipped: int}
     */
    private array $counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];

    /**
     * The records refused so far, by index.
     *
     * @var array<int, array{index: int, identifier: string|null, errors: array<string, list<string>>}>
     */
    private array $failures = [];

    public function __construct(
        private readonly ProductIndex $index,
        private readonly ProductChangeCapture $capture,
        private readonly BulkProductWriter $writer,
        private readonly ExternalIds $externalIds,
        private readonly Flags $flags,
        private readonly Associations $associations,
    ) {}

    /**
     * Records that fail are reported in the result, not thrown.
     */
    public function execute(UpsertProductsData $data): UpsertProductsResult
    {
        ProductsUpsertingActionEvent::dispatch($data);

        $result = $this->perform($data);

        ProductsUpsertedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(UpsertProductsData $data): UpsertProductsResult
    {
        $this->counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];
        $this->failures = [];

        // One index sync for the batch, queued as the deferral ends: inside
        // the suppression, so it is not captured either.
        $batch = fn (): UpsertProductsResult => $this->index->deferring(fn (): UpsertProductsResult => $this->writeBatch($data));

        return $data->captureChanges ? $batch() : $this->capture->suppress($batch);
    }

    /**
     * Writes no record itself, only the unchanged records' sync stamp:
     * BulkProductWriter and the create and update actions each write inside
     * their own transaction, so one record's failure never rolls back its
     * neighbours.
     */
    private function writeBatch(UpsertProductsData $data): UpsertProductsResult
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

        // One value validator for the batch: the attributes and option codes
        // every record's values send are read once, up front.
        $values = app(ValueValidator::class);
        $values->prime(array_map(fn (array $record): mixed => $record['values'] ?? null, $records));

        [$creates, $updates, $unchanged, $afterUpdates] = $this->validate($data, $records, $matched, $values);

        $this->externalIds->touch($unchanged, ProductModel::class);
        $this->createAll(array_diff_key($creates, $afterUpdates), $records, $values);
        $this->updateAll($updates, $records);

        foreach (array_intersect_key($creates, $afterUpdates) as $index => $create) {
            $this->createOne($index, $create, $records[$index]);
        }

        ksort($this->failures);

        return new UpsertProductsResult(
            created: $this->counts['created'],
            updated: $this->counts['updated'],
            unchanged: $this->counts['unchanged'],
            skipped: $this->counts['skipped'],
            failures: array_values($this->failures),
        );
    }

    /**
     * The stored product each record matches, by index, loaded together with
     * what the preview compares. Identifiers and external ids match under
     * KeyNormalizer, as the database compares them.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, ProductModel>
     */
    private function match(UpsertProductsData $data, array $records): array
    {
        $keys = [];

        foreach ($records as $index => $record) {
            $key = $data->matchesExternalIds()
                ? (is_array($record['external_ids'] ?? null) ? ($record['external_ids'][$data->match] ?? null) : null)
                : ($record['identifier'] ?? null);

            if (is_string($key) && $key !== '') {
                $keys[$index] = $key;
            }
        }

        if ($keys === []) {
            return [];
        }

        $products = [];

        if ($data->matchesExternalIds()) {
            $holders = $this->externalIds->find(ProductModel::class, $data->match, array_values($keys));
            $loaded = ProductModel::query()->with(self::MATCHED_WITH)->whereKey(array_values(array_unique($holders)))->get()->keyBy('id');

            foreach ($keys as $index => $key) {
                $product = $loaded->get($holders[$key] ?? '');

                if ($product instanceof ProductModel) {
                    $products[$index] = $product;
                }
            }

            return $products;
        }

        $byIdentifier = [];

        foreach (array_chunk(array_values(array_unique($keys)), 1000) as $chunk) {
            foreach (ProductModel::query()->with(self::MATCHED_WITH)->whereIn('identifier', $chunk)->get() as $product) {
                $byIdentifier[KeyNormalizer::normalize($product->identifier)] = $product;
            }
        }

        foreach ($keys as $index => $key) {
            $product = $byIdentifier[KeyNormalizer::normalize($key)] ?? null;

            if ($product !== null) {
                $products[$index] = $product;
            }
        }

        return $products;
    }

    /**
     * Validate each record the mode writes, check `assign` where its flags
     * change, and set aside the matched records that change nothing: the
     * creates, the updates, the ids of the unchanged products, and the
     * creates to write after the updates.
     *
     * External ids are claimed here, in batch order, whichever way each
     * record is later written: a record sending a (source, id) that an
     * earlier record of the batch sends fails, and the first persists. A new
     * record taking an id that an earlier update releases is written after
     * the updates, once the id is free.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int, ProductModel>  $matched
     * @return array{0: array<int, CreateProductData>, 1: array<int, array{0: ProductModel, 1: UpdateProductData}>, 2: list<string>, 3: array<int, true>}
     */
    private function validate(UpsertProductsData $data, array $records, array $matched, ValueValidator $values): array
    {
        $presence = $this->presence($records, $matched);
        // One set of rules reads what it needs once for the batch.
        $createRules = CreateProductData::rules();
        $updateRules = UpdateProductData::rules();

        $creates = [];
        $updates = [];
        $unchanged = [];
        $afterUpdates = [];
        $newIdentifiers = [];
        $matchedProducts = [];
        /** @var array<string, array<string, true>> $claimedIds */
        $claimedIds = [];
        /** @var array<string, array<string, true>> $releasedIds */
        $releasedIds = [];

        foreach ($records as $index => $record) {
            $product = $matched[$index] ?? null;

            if ($product === null ? ! $data->mode->creates() : ! $data->mode->updates()) {
                $this->counts['skipped']++;

                continue;
            }

            try {
                if ($product === null) {
                    $identifier = is_string($record['identifier'] ?? null) ? KeyNormalizer::normalize($record['identifier']) : null;

                    if ($identifier !== null && isset($newIdentifiers[$identifier])) {
                        throw ValidationException::withMessages(['identifier' => sprintf('Another record of the batch has the identifier "%s".', $record['identifier'])]);
                    }

                    $create = $this->validateNew($record, $createRules, $presence);
                    $wanted = self::sentExternalIds($create->externalIds ?? []);

                    self::refuseClaimed($wanted, $claimedIds);

                    $creates[$index] = $create;
                    self::claim($wanted, $claimedIds);

                    if (self::anyReleased($wanted, $releasedIds)) {
                        $afterUpdates[$index] = true;
                    }

                    if ($identifier !== null) {
                        $newIdentifiers[$identifier] = true;
                    }

                    continue;
                }

                if (isset($matchedProducts[$product->id])) {
                    throw ValidationException::withMessages([
                        $data->matchesExternalIds() ? 'external_ids.'.$data->match : 'identifier' => sprintf('Another record of the batch matches product "%s".', $product->identifier),
                    ]);
                }

                $matchedProducts[$product->id] = true;
                $update = $this->validateMatched($record, $product, $updateRules, $presence);
                $sent = $update->externalIds instanceof Missing ? [] : $update->externalIds;
                $wanted = self::sentExternalIds($sent);

                self::refuseClaimed($wanted, $claimedIds);

                if ($this->unchanged($product, $update, $values)) {
                    $this->counts['unchanged']++;
                    $unchanged[] = $product->id;
                } else {
                    $updates[$index] = [$product, $update];
                    self::release($product, $sent, $releasedIds);
                }

                self::claim($wanted, $claimedIds);
            } catch (ValidationException|AuthorizationException $exception) {
                $this->failWith($index, $record, $product, $exception);
            }
        }

        return [$creates, $updates, $unchanged, $afterUpdates];
    }

    /**
     * The ids a record sends, by source, each with an id.
     *
     * @param  array<array-key, string|null>  $map
     * @return array<string, string>
     */
    private static function sentExternalIds(array $map): array
    {
        $sent = [];

        foreach ($map as $source => $externalId) {
            if (is_string($externalId)) {
                $sent[(string) $source] = $externalId;
            }
        }

        return $sent;
    }

    /**
     * Refuse the ids an earlier record of the batch claimed.
     *
     * @param  array<string, string>  $wanted
     * @param  array<string, array<string, true>>  $claimed
     *
     * @throws ValidationException
     */
    private static function refuseClaimed(array $wanted, array $claimed): void
    {
        $errors = [];

        foreach ($wanted as $source => $externalId) {
            if (isset($claimed[$source][KeyNormalizer::normalize($externalId)])) {
                $errors['external_ids.'.$source] = sprintf('Another record of the batch has the %s id "%s".', $source, $externalId);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, string>  $wanted
     * @param  array<string, array<string, true>>  $claimed
     */
    private static function claim(array $wanted, array &$claimed): void
    {
        foreach ($wanted as $source => $externalId) {
            $claimed[$source][KeyNormalizer::normalize($externalId)] = true;
        }
    }

    /**
     * Note the stored ids an update removes or replaces: once it is written,
     * a later record of the batch may take them.
     *
     * @param  array<array-key, string|null>  $sent
     * @param  array<string, array<string, true>>  $released
     */
    private static function release(ProductModel $product, array $sent, array &$released): void
    {
        $stored = ExternalIds::present($product->externalIds);

        foreach ($sent as $source => $externalId) {
            $held = $stored[(string) $source] ?? null;

            if ($held !== null && ! self::same($externalId, $held)) {
                $released[(string) $source][KeyNormalizer::normalize($held)] = true;
            }
        }
    }

    /**
     * @param  array<string, string>  $wanted
     * @param  array<string, array<string, true>>  $released
     */
    private static function anyReleased(array $wanted, array $released): bool
    {
        foreach ($wanted as $source => $externalId) {
            if (isset($released[$source][KeyNormalizer::normalize($externalId)])) {
                return true;
            }
        }

        return false;
    }

    /**
     * A new record, validated as CreateProductData. Giving it flags needs
     * `assign`, asked before anything is written.
     *
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $rules
     *
     * @throws ValidationException
     * @throws AuthorizationException
     */
    private function validateNew(array $record, array $rules, BatchPresenceVerifier $presence): CreateProductData
    {
        $validator = Validator::make($record, $rules);
        $validator->setPresenceVerifier($presence);

        $data = CreateProductData::fromValidated($validator->validate());

        if (($data->flags ?? []) !== []) {
            $this->flags->authorizeAssign(new ProductModel(['identifier' => $data->identifier]));
        }

        return $data;
    }

    /**
     * A matched record, validated as UpdateProductData. Its identifier and
     * parent name the product, and are taken off once they agree with it; a
     * variant's family and owner come from its model and are not compared,
     * as an export holds them. Changing the flags needs `assign`.
     *
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $rules
     *
     * @throws ValidationException
     * @throws AuthorizationException
     */
    private function validateMatched(array $record, ProductModel $product, array $rules, BatchPresenceVerifier $presence): UpdateProductData
    {
        if (($record['identifier'] ?? null) === null || (is_string($record['identifier']) && self::same($record['identifier'], $product->identifier))) {
            unset($record['identifier']);
        }

        if (array_key_exists('parent', $record) && ! self::same($record['parent'], $product->parent?->code)) {
            throw ValidationException::withMessages([
                'parent' => sprintf('The parent of product "%s" cannot be changed.', $product->identifier),
            ]);
        }

        unset($record['parent']);

        if ($product->isVariant()) {
            unset($record['family'], $record['owner']);
        }

        $validator = Validator::make($record, $rules);
        $validator->setPresenceVerifier($presence);

        $data = UpdateProductData::fromValidated($validator->validate());

        if (is_array($data->flags) && self::codes($data->flags) !== self::codes($product->flags->pluck('code')->all())) {
            $this->flags->authorizeAssign($product);
        }

        return $data;
    }

    /**
     * Whether every field the record sends equals what the product holds,
     * compared as UpdateProductAction would store it.
     *
     * @throws ValidationException When the values sent are invalid.
     */
    private function unchanged(ProductModel $product, UpdateProductData $data, ValueValidator $values): bool
    {
        if (! $data->family instanceof Missing && ! self::same($data->family, $product->family?->code)) {
            return false;
        }

        if (! $data->owner instanceof Missing && ! self::same($data->owner, $product->owner?->code)) {
            return false;
        }

        if (! $data->enabled instanceof Missing && $data->enabled !== $product->enabled) {
            return false;
        }

        if (! $data->categories instanceof Missing && self::codes($data->categories ?? []) !== self::codes($product->categories->pluck('code')->all())) {
            return false;
        }

        if (! $data->flags instanceof Missing && self::codes($data->flags) !== self::codes($product->flags->pluck('code')->all())) {
            return false;
        }

        if (! $data->externalIds instanceof Missing) {
            $stored = ExternalIds::present($product->externalIds);

            foreach ($data->externalIds as $source => $externalId) {
                if (($stored[(string) $source] ?? null) !== $externalId) {
                    return false;
                }
            }
        }

        if (! $this->sameAssociations($product, $data)) {
            return false;
        }

        if (! $data->values instanceof Missing) {
            $stored = $product->ownValues();

            // Validated and normalized as the update would store them.
            return self::canonical(Values::apply($stored, $values->validate($data->values, null))) === self::canonical($stored);
        }

        return true;
    }

    /**
     * Whether each association type the record sends already has exactly
     * those targets, in that order.
     */
    private function sameAssociations(ProductModel $product, UpdateProductData $data): bool
    {
        $own = null;

        foreach (['associations' => $data->associations, 'quantified_associations' => $data->quantifiedAssociations] as $key => $sent) {
            if ($sent instanceof Missing) {
                continue;
            }

            $own ??= $this->associations->present($product, inherit: false);

            foreach ($sent as $code => $groups) {
                $stored = $own[$key][(string) $code] ?? [];

                foreach (['products', 'product_models'] as $kind) {
                    $wanted = is_array($groups) && is_array($groups[$kind] ?? null) ? array_values($groups[$kind]) : [];

                    if (self::targets($wanted) !== self::targets($stored[$kind] ?? [])) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Write the new records: the simple ones in bulk, falling back to one by
     * one when the bulk write fails, then the rest, which may name them.
     *
     * @param  array<int, CreateProductData>  $creates
     * @param  array<int, array<string, mixed>>  $records
     */
    private function createAll(array $creates, array $records, ValueValidator $values): void
    {
        $simple = array_filter($creates, BulkProductWriter::takes(...));

        [$prepared, $refused] = $this->writer->prepare($simple, $values);

        foreach ($refused as $index => $errors) {
            $this->fail($index, $creates[$index]->identifier, $errors);
        }

        try {
            $this->counts['created'] += count($this->writer->store($prepared));
        } catch (Throwable) {
            // Nothing of the bulk write was kept: write its records one by
            // one, so whatever broke it fails only the record it belongs to.
            foreach (array_keys($prepared) as $index) {
                $this->createOne($index, $creates[$index], $records[$index]);
            }
        }

        foreach (array_diff_key($creates, $simple) as $index => $create) {
            $this->createOne($index, $create, $records[$index]);
        }
    }

    /**
     * @param  array<int, array{0: ProductModel, 1: UpdateProductData}>  $updates
     * @param  array<int, array<string, mixed>>  $records
     */
    private function updateAll(array $updates, array $records): void
    {
        foreach ($updates as $index => [$product, $update]) {
            try {
                app(UpdateProductAction::class)->execute($product, $update);
                $this->counts['updated']++;
            } catch (ValidationException|AuthorizationException|ModelNotFoundException|QueryException $exception) {
                $this->failWith($index, $records[$index], $product, $exception);
            }
        }
    }

    /**
     * A database error fails only the record it belongs to: the action's
     * transaction has rolled its writes back.
     *
     * @param  array<string, mixed>  $record
     */
    private function createOne(int $index, CreateProductData $create, array $record): void
    {
        try {
            app(CreateProductAction::class)->execute($create);
            $this->counts['created']++;
        } catch (ValidationException|AuthorizationException|ModelNotFoundException|QueryException $exception) {
            $this->failWith($index, $record, null, $exception);
        }
    }

    /**
     * A verifier that answers the batch's `exists` and `unique` rules from
     * one query per column.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int, ProductModel>  $matched
     */
    private function presence(array $records, array $matched): BatchPresenceVerifier
    {
        /** @var PresenceVerifierInterface $verifier */
        $verifier = app('validation.presence');
        $presence = new BatchPresenceVerifier($verifier);
        $new = array_diff_key($records, $matched);
        $sent = fn (array $from, string $key): array => array_values(array_map(fn (array $record): mixed => $record[$key] ?? null, $from));

        $presence->prime(ProductModel::class, 'identifier', $sent($new, 'identifier'));
        $presence->prime(ProductModelModel::class, 'code', $sent($new, 'parent'));
        $presence->prime(FamilyModel::class, 'code', $sent($records, 'family'));
        $presence->prime(OwnerModel::class, 'code', $sent($records, 'owner'));
        $presence->prime(CategoryModel::class, 'code', array_merge(...array_map(
            fn (mixed $categories): array => is_array($categories) ? array_values($categories) : [],
            $sent($records, 'categories'),
        )));

        return $presence;
    }

    /**
     * A database error is reported without its SQL; one other than a unique
     * key taken mid-write goes to the exception handler as well.
     *
     * @param  array<string, mixed>  $record
     * @param  ValidationException|AuthorizationException|ModelNotFoundException<Model>|QueryException  $exception
     */
    private function failWith(int $index, array $record, ?ProductModel $product, ValidationException|AuthorizationException|ModelNotFoundException|QueryException $exception): void
    {
        $identifier = is_string($record['identifier'] ?? null) ? $record['identifier'] : $product?->identifier;

        if ($exception instanceof QueryException && ! $exception instanceof UniqueConstraintViolationException) {
            report($exception);
        }

        /** @var array<string, list<string>> $errors */
        $errors = match (true) {
            $exception instanceof ValidationException => $exception->errors(),
            // Only a change of flags is authorized per record.
            $exception instanceof AuthorizationException => ['flags' => [$exception->getMessage()]],
            $exception instanceof UniqueConstraintViolationException => ['record' => ['Another record took one of this record\'s unique keys while it was written.']],
            $exception instanceof QueryException => ['record' => ['The record could not be written to the database.']],
            default => ['record' => [$exception->getMessage()]],
        };

        $this->fail($index, $identifier, $errors);
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function fail(int $index, ?string $identifier, array $errors): void
    {
        $this->failures[$index] = ['index' => $index, 'identifier' => $identifier, 'errors' => $errors];
    }

    /**
     * Whether two codes or identifiers name the same record; two nulls do.
     */
    private static function same(mixed $sent, ?string $stored): bool
    {
        if ($sent === null || $stored === null) {
            return $sent === $stored;
        }

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
     * Association targets in order: identifiers, with quantities when given.
     *
     * @param  array<int, mixed>  $targets
     * @return list<string>
     */
    private static function targets(array $targets): array
    {
        return array_values(array_map(fn (mixed $target): string => is_array($target)
            ? KeyNormalizer::normalize((string) ($target['identifier'] ?? '')).'*'.(int) ($target['quantity'] ?? 1)
            : KeyNormalizer::normalize((string) $target), $targets));
    }

    /**
     * A value with every map's keys sorted, so two stored forms compare
     * equal whatever order they were written in. Lists keep their order.
     */
    private static function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(self::canonical(...), $value);
    }
}
