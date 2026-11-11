<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Services;

use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Services\Values;
use RefactorCircus\Showroom\Domains\Attribute\Services\ValueValidator;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;
use RefactorCircus\Showroom\Support\KeyNormalizer;
use RefactorCircus\Showroom\Support\Slugs;
use RefactorCircus\Showroom\Support\Tables;
use Throwable;

/**
 * Creates many new simple products at once, leaving exactly the rows
 * CreateProductAction leaves for each: the product with its slug, its
 * categories, external ids, flags and unique values, and its one `created`
 * version. UpsertProductsParityTest holds the two to the same rows.
 *
 * Simple means a product with no parent and no associations (takes()): the
 * rules such a product is held to — its owner owns products, its values fit
 * its family, its external ids and unique values are its own — are checked
 * here for the whole batch with a few queries, rather than a few per product.
 *
 * prepare() checks a batch and builds its rows without writing; store()
 * writes them in one transaction. A record prepare() refuses is refused with
 * the errors CreateProductAction would give it, in the order it checks them.
 */
final class BulkProductWriter
{
    /**
     * Rows per insert statement, under every driver's placeholder limit.
     */
    private const int ROWS_PER_INSERT = 200;

    public function __construct(
        private readonly Slugs $slugs,
        private readonly ExternalIds $externalIds,
        private readonly ProductIndex $index,
        private readonly Auth $auth,
    ) {}

    /**
     * Whether a new product can be written in bulk: one with no parent and
     * no associations.
     */
    public static function takes(CreateProductData $data): bool
    {
        return $data->parent === null
            && ($data->associations ?? []) === []
            && ($data->quantifiedAssociations ?? []) === [];
    }

    /**
     * Check a batch of new simple products and build their rows. Records
     * must have distinct identifiers, and each must be one takes() accepts.
     * Returns the rows to store, and the errors of each record refused, by
     * index.
     *
     * @param  array<int, CreateProductData>  $records  By index in the batch.
     * @param  ValueValidator|null  $validator  One the caller already primed for the batch; else one is made and primed here.
     * @return array{0: array<int, array{product: array<string, mixed>, categories: list<array<string, mixed>>, external_ids: list<array<string, mixed>>, flags: list<array<string, mixed>>, unique_values: list<array<string, mixed>>, version: array<string, mixed>}>, 1: array<int, array<string, list<string>>>}
     */
    public function prepare(array $records, ?ValueValidator $validator = null): array
    {
        if ($records === []) {
            return [[], []];
        }

        $families = self::byCode(FamilyModel::query()->with('familyAttributes'), array_map(fn (CreateProductData $data): ?string => $data->family, $records));
        $owners = self::byCode(OwnerModel::query()->with('type'), array_map(fn (CreateProductData $data): ?string => $data->owner, $records));
        $categories = self::byCode(CategoryModel::query(), array_merge(...array_values(array_map(fn (CreateProductData $data): array => $data->categories ?? [], $records))));
        $flags = self::byCode(FlagModel::query(), array_merge(...array_values(array_map(fn (CreateProductData $data): array => $data->flags ?? [], $records))));
        $uniqueAttributes = AttributeModel::query()->where('is_unique', true)->get(['id', 'code']);

        // One validator for the batch reads each attribute, and each option
        // code sent, once.
        if ($validator === null) {
            $validator = app(ValueValidator::class);
            $validator->prime(array_map(fn (CreateProductData $data): mixed => $data->values, $records));
        }

        $failures = [];
        $products = [];

        foreach ($records as $index => $data) {
            try {
                $products[$index] = $this->product($data, $families, $owners, $validator);
            } catch (ValidationException $exception) {
                /** @var array<string, list<string>> $errors */
                $errors = $exception->errors();
                $failures[$index] = $errors;
            }
        }

        [$kept, $refused] = $this->claimKeys($records, $products, $uniqueAttributes);
        $failures = $refused + $failures;

        $slugs = $this->slugs->forMany('products', array_map(fn (ProductModel $product): string => $product->identifier, $kept));
        $author = $this->auth->guard()->user();
        $prepared = [];

        foreach ($kept as $index => $product) {
            $data = $records[$index];
            $product->slug = $slugs[$index];
            $product->setUniqueIds();
            $product->updateTimestamps();

            $categoryRows = [];
            $categoryCodes = [];

            foreach (array_unique(array_map(KeyNormalizer::normalize(...), $data->categories ?? [])) as $code) {
                $category = $categories[$code] ?? null;

                if ($category !== null) {
                    $categoryRows[] = ['category_id' => $category->id, 'product_id' => $product->id];
                    $categoryCodes[] = $category->code;
                }
            }

            $flagRows = [];

            foreach (array_unique(array_map(KeyNormalizer::normalize(...), $data->flags ?? [])) as $code) {
                $flag = $flags[$code] ?? null;

                if ($flag !== null) {
                    $flagRows[] = ['flag_id' => $flag->id, 'subject_type' => $product->getMorphClass(), 'subject_id' => $product->id];
                }
            }

            $prepared[$index] = [
                'product' => $product->getAttributes(),
                'categories' => $categoryRows,
                'external_ids' => $this->externalIdRows($product, $data),
                'flags' => $flagRows,
                'unique_values' => array_map(
                    fn (array $unique): array => ['attribute_id' => $unique['attribute_id'], 'product_id' => $product->id, 'value_hash' => $unique['hash']],
                    self::uniqueValues($product, $uniqueAttributes),
                ),
                'version' => $this->createdVersion($product, $categoryCodes, $author),
            ];
        }

        ksort($failures);

        return [$prepared, $failures];
    }

    /**
     * Write what prepare() built, in one transaction, and queue the
     * products' index sync. Nothing is written when any of it fails.
     *
     * @param  array<int, array{product: array<string, mixed>, categories: list<array<string, mixed>>, external_ids: list<array<string, mixed>>, flags: list<array<string, mixed>>, unique_values: list<array<string, mixed>>, version: array<string, mixed>}>  $prepared
     * @return array<int, string> The new products' ids, by index.
     *
     * @throws Throwable When a write fails; the caller can write the records one by one instead.
     */
    public function store(array $prepared): array
    {
        if ($prepared === []) {
            return [];
        }

        return Tables::db()->transaction(function () use ($prepared): array {
            $this->insert('products', array_column($prepared, 'product'));
            $this->insert('category_product', array_merge(...array_column($prepared, 'categories')));
            $this->insert('external_ids', array_merge(...array_column($prepared, 'external_ids')));
            $this->insert('flag_assignments', array_merge(...array_column($prepared, 'flags')));
            $this->insert('product_unique_values', array_merge(...array_column($prepared, 'unique_values')));
            $this->insert('versions', array_column($prepared, 'version'));

            $ids = array_map(fn (array $rows): string => (string) $rows['product']['id'], $prepared);

            $this->index->queue(array_values($ids));

            return $ids;
        });
    }

    /**
     * A new product as CreateProductAction builds it, before its slug: its
     * owner must own products, and its values must fit its family.
     *
     * @param  array<string, FamilyModel>  $families
     * @param  array<string, OwnerModel>  $owners
     *
     * @throws ValidationException
     */
    private function product(CreateProductData $data, array $families, array $owners, ValueValidator $validator): ProductModel
    {
        $family = $data->family === null ? null : ($families[KeyNormalizer::normalize($data->family)] ?? null);
        $owner = $data->owner === null ? null : ($owners[KeyNormalizer::normalize($data->owner)] ?? null);

        if ($data->family !== null && $family === null) {
            throw ValidationException::withMessages(['family' => sprintf('Family "%s" does not exist.', $data->family)]);
        }

        if ($data->owner !== null && $owner === null) {
            throw ValidationException::withMessages(['owner' => sprintf('Owner "%s" does not exist.', $data->owner)]);
        }

        // As AssignsOwners::ownerFor() refuses it.
        if ($owner !== null && ! $owner->type->owns_products) {
            throw ValidationException::withMessages([
                'owner' => sprintf('Owner "%s" is a %s, which does not own products.', $owner->code, $owner->type->code),
            ]);
        }

        // As WritesProducts::settable() reads it for a product with no parent.
        $values = Values::apply([], $validator->validate(
            $data->values,
            $family?->familyAttributes,
            $family === null ? '' : sprintf('is not in family "%s".', $family->code),
        ));

        $product = new ProductModel([
            'identifier' => $data->identifier,
            'enabled' => $data->enabled,
            'values' => $values,
        ]);
        $product->family()->associate($family);
        $product->parent()->associate(null);
        $product->owner()->associate($owner);

        return $product;
    }

    /**
     * Refuse, in batch order, each product whose external ids or unique
     * values another product holds — stored, or kept earlier in the batch —
     * with the errors ExternalIds::write() and UniqueValues::sync() give.
     *
     * @param  array<int, CreateProductData>  $records
     * @param  array<int, ProductModel>  $products
     * @param  Collection<int, AttributeModel>  $uniqueAttributes
     * @return array{0: array<int, ProductModel>, 1: array<int, array<string, list<string>>>}
     */
    private function claimKeys(array $records, array $products, Collection $uniqueAttributes): array
    {
        $heldIds = [];

        foreach ($this->wantedExternalIds($records, $products) as $source => $ids) {
            $heldIds[$source] = $this->externalIds->find(ProductModel::class, $source, $ids);
        }

        $heldValues = $this->heldUniqueValues($products, $uniqueAttributes);
        $claimedIds = [];
        $claimedValues = [];
        $kept = [];
        $refused = [];

        foreach ($products as $index => $product) {
            $errors = [];
            $wanted = self::externalIds($records[$index]);

            foreach ($wanted as $source => $externalId) {
                if (isset($heldIds[$source][$externalId]) || isset($claimedIds[$source][KeyNormalizer::normalize($externalId)])) {
                    $errors['external_ids.'.$source] = [sprintf('Another product already has the %s id "%s".', $source, $externalId)];
                }
            }

            if ($errors === []) {
                foreach (self::uniqueValues($product, $uniqueAttributes) as $unique) {
                    $holder = $heldValues[$unique['attribute_id']][$unique['hash']] ?? $claimedValues[$unique['attribute_id']][$unique['hash']] ?? null;

                    if ($holder !== null) {
                        $errors['values.'.$unique['code']] = [sprintf(
                            'The value %s of unique attribute "%s" is already used by product "%s".',
                            json_encode($unique['data'], JSON_THROW_ON_ERROR),
                            $unique['code'],
                            $holder,
                        )];

                        // UniqueValues::sync() stops at the first.
                        break;
                    }
                }
            }

            if ($errors !== []) {
                $refused[$index] = $errors;

                continue;
            }

            foreach ($wanted as $source => $externalId) {
                $claimedIds[$source][KeyNormalizer::normalize($externalId)] = true;
            }

            foreach (self::uniqueValues($product, $uniqueAttributes) as $unique) {
                $claimedValues[$unique['attribute_id']][$unique['hash']] = $product->identifier;
            }

            $kept[$index] = $product;
        }

        return [$kept, $refused];
    }

    /**
     * The external ids the batch's products want, by source.
     *
     * @param  array<int, CreateProductData>  $records
     * @param  array<int, ProductModel>  $products
     * @return array<string, list<string>>
     */
    private function wantedExternalIds(array $records, array $products): array
    {
        $wanted = [];

        foreach (array_keys($products) as $index) {
            foreach (self::externalIds($records[$index]) as $source => $externalId) {
                $wanted[$source][] = $externalId;
            }
        }

        return $wanted;
    }

    /**
     * Who holds each unique value the batch's products want: the holder's
     * identifier, by attribute id and value hash. One query per unique
     * attribute.
     *
     * @param  array<int, ProductModel>  $products
     * @param  Collection<int, AttributeModel>  $uniqueAttributes
     * @return array<string, array<string, string>>
     */
    private function heldUniqueValues(array $products, Collection $uniqueAttributes): array
    {
        $hashes = [];

        foreach ($products as $product) {
            foreach (self::uniqueValues($product, $uniqueAttributes) as $unique) {
                $hashes[$unique['attribute_id']][] = $unique['hash'];
            }
        }

        $held = [];
        $products = Tables::name('products');

        foreach ($hashes as $attributeId => $attributeHashes) {
            foreach (array_chunk(array_values(array_unique($attributeHashes)), 1000) as $chunk) {
                $holders = Tables::db()->table(Tables::name('product_unique_values').' as unique_values')
                    ->join($products, $products.'.id', '=', 'unique_values.product_id')
                    ->where('unique_values.attribute_id', $attributeId)
                    ->whereIn('unique_values.value_hash', $chunk)
                    ->get(['unique_values.value_hash', $products.'.identifier']);

                foreach ($holders as $holder) {
                    $held[(string) $attributeId][(string) $holder->value_hash] = (string) $holder->identifier;
                }
            }
        }

        return $held;
    }

    /**
     * The rows of a new product's external ids, each stamped synced now, as
     * ExternalIds::write() stamps the ids it writes.
     *
     * @return list<array<string, mixed>>
     */
    private function externalIdRows(ProductModel $product, CreateProductData $data): array
    {
        $rows = [];
        $now = Carbon::now();

        foreach (self::externalIds($data) as $source => $externalId) {
            $row = new ExternalIdModel([
                'subject_type' => $product->getMorphClass(),
                'subject_id' => $product->id,
                'source' => $source,
                'external_id' => $externalId,
                'synced_at' => $now,
            ]);
            $row->setUniqueIds();
            $row->updateTimestamps();

            $rows[] = $row->getAttributes();
        }

        return $rows;
    }

    /**
     * The row of a new product's first version, as Versions::record()
     * writes it: the product's snapshot, every field in it as a change.
     *
     * @param  list<string>  $categoryCodes
     * @return array<string, mixed>
     */
    private function createdVersion(ProductModel $product, array $categoryCodes, mixed $author): array
    {
        sort($categoryCodes, SORT_STRING);

        // As Versions::snapshot() reads a product with no parent and no associations.
        $snapshot = [
            'identifier' => $product->identifier,
            'family' => $product->family?->code,
            'parent' => null,
            'owner' => $product->owner?->code,
            'enabled' => $product->enabled,
            'status' => $product->status->value,
            'values' => Values::toStandard($product->ownValues()),
            'inherited_values' => Values::toStandard([]),
            'categories' => $categoryCodes,
            'associations' => [],
            'quantified_associations' => [],
        ];

        // As Versions::diff() reads it against no earlier version.
        $changes = [];

        foreach ($snapshot as $key => $value) {
            if ($key !== 'values' && $key !== 'inherited_values' && $value !== null) {
                $changes[$key] = ['old' => null, 'new' => $value];
            }
        }

        foreach ($snapshot['values'] as $code => $slots) {
            $changes['values.'.$code] = ['old' => null, 'new' => $slots];
        }

        $version = new VersionModel([
            'versionable_type' => $product->getMorphClass(),
            'versionable_id' => $product->id,
            'version' => 1,
            'action' => 'created',
            'snapshot' => $snapshot,
            'changes' => $changes,
            'comment' => null,
            'author_type' => $author instanceof Model ? $author->getMorphClass() : null,
            'author_id' => $author instanceof Model ? (string) $author->getKey() : null,
        ]);
        $version->setUniqueIds();
        $version->updateTimestamps();

        return $version->getAttributes();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insert(string $table, array $rows): void
    {
        foreach (array_chunk($rows, self::ROWS_PER_INSERT) as $chunk) {
            Tables::db()->table(Tables::name($table))->insert($chunk);
        }
    }

    /**
     * The external ids a new product is given: the sources sent with an id.
     *
     * @return array<string, string>
     */
    private static function externalIds(CreateProductData $data): array
    {
        $ids = [];

        foreach ($data->externalIds ?? [] as $source => $externalId) {
            if (is_string($externalId)) {
                $ids[(string) $source] = $externalId;
            }
        }

        return $ids;
    }

    /**
     * The product's values of unique attributes, each with the hash
     * UniqueValues keeps of it, in attribute order.
     *
     * @param  Collection<int, AttributeModel>  $uniqueAttributes
     * @return list<array{attribute_id: string, code: string, data: mixed, hash: string}>
     */
    private static function uniqueValues(ProductModel $product, Collection $uniqueAttributes): array
    {
        $unique = [];

        foreach ($uniqueAttributes as $attribute) {
            // Unique attributes are neither localizable nor scopable.
            $data = Values::get($product->ownValues(), $attribute->code);

            if ($data !== null) {
                $unique[] = [
                    'attribute_id' => (string) $attribute->id,
                    'code' => $attribute->code,
                    'data' => $data,
                    'hash' => hash('sha256', json_encode($data, JSON_THROW_ON_ERROR)),
                ];
            }
        }

        return $unique;
    }

    /**
     * The records a query finds for these codes, by normalized code, in one
     * query per thousand codes.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string|null>  $codes
     * @return array<string, TModel>
     */
    private static function byCode(Builder $query, array $codes): array
    {
        $codes = array_values(array_unique(array_filter($codes, is_string(...))));
        $found = [];

        foreach (array_chunk($codes, 1000) as $chunk) {
            foreach ((clone $query)->whereIn('code', $chunk)->get() as $record) {
                $found[KeyNormalizer::normalize((string) $record->getAttribute('code'))] = $record;
            }
        }

        return $found;
    }
}
