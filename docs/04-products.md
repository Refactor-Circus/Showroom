# Products and variants

## Values

Attribute values are sent and returned in one shape everywhere — a list of slots per attribute:

```json
{
  "name": [{"locale": null, "scope": null, "data": "Classic tee"}],
  "description": [
    {"locale": "en", "scope": null, "data": "Soft cotton"},
    {"locale": "fr", "scope": null, "data": "Coton doux"}
  ]
}
```

- `locale` is required for localizable attributes and must be an existing locale; `scope` is required for scopable attributes and must be an existing channel; both are null otherwise. See [Channels and locales](09-channels.md).
- On update, only the slots you send change. `"data": null` clears a slot.
- `data` by type:

| Type | `data` |
|---|---|
| `text`, `textarea` | string |
| `number` | integer |
| `decimal` | number or numeric string — returned as a string, so no precision is lost |
| `boolean` | `true` / `false` |
| `date` | `"2026-03-01"` |
| `select` | an option code |
| `multiselect` | a list of option codes |
| `price` | `[{"amount": "19.99", "currency": "USD"}]` |
| `metric` | `{"amount": "180", "unit": "gram"}` |

Each value is checked against its attribute's settings (length, bounds, decimals, currencies, options) and errors come back at the path you sent, such as `values.weight.0.data.unit`.

Values are stored as JSON on the product row, keyed by attribute, channel and locale.

## Simple products

A product has an `identifier` (its SKU), an optional `family`, an optional `owner` (see [Ownership](06-ownership.md)), `categories` (see [Taxonomy](07-taxonomy.md)), `associations` and `quantified_associations` (see [Associations](10-associations.md)), `external_ids`, `flags` (see [Flags](14-flags.md)), an `enabled` flag and its values. Showroom gives it a ULID `id` and a `slug`.

- With a family, only the family's attributes can be set.
- Without one, any attribute can.
- Changing the family keeps stored values, as in any PIM; values outside the new family stay hidden in the data until the product moves back.
- Completeness (required attributes) is not enforced on save.

## Identifiers

The `identifier` is the product's SKU as your ERP knows it, spaces and `/` included: `ML20906 HPSK 630 SEC M91 M92 LC LH` and `83AL-IC/40 4 RED ALUM` are both valid. It is unique, at most 191 characters, and never changes.

The rule comes from `showroom.products.identifier_pattern`. The default refuses an empty identifier, leading or trailing whitespace, and control characters. Set a stricter pattern to hold SKUs to your own format. A product that doesn't match then fails validation at `identifier`. A pattern that doesn't compile throws an exception naming the key, rather than a warning on every request.

Identifiers compare without regard to case or accents, as the database's default collation does: `tee-1` and `TEE-1` collide at `identifier`.

Inputs that reference a product (`parent`, associations, filters, MCP tool arguments such as an association target) keep using the identifier. URLs never do: see [Looking up a product](#looking-up-a-product).

## Slugs

Every product gets a `slug` when it's created: `Str::slug` of its identifier, with `/`, `.` and other punctuation read as word breaks, so `83AL-IC/40 4 RED ALUM` becomes `83al-ic-40-4-red-alum`. A slug is URL-safe, unique and at most 191 characters, and never changes, even when the product's values do.

- A slug another product holds gets the first free suffix: `a-b`, then `a-b-2`, `a-b-3`.
- An identifier that slugifies to nothing (`///`) gets `product`, then `product-2`.
- A slug that would read as a route segment (`Slugs::RESERVED`: `create`, `revert`, `transitions`, `versions`) is suffixed: `versions` becomes `versions-2`.

`slug` is read-only: sending it on create or update is refused. Clients that send a whole resource back on update must strip `slug` and `effective_flags`.

## Looking up a product

API paths, Atrium links, MCP `product` arguments and the show, update, delete, transition, versions and revert actions find a product by its ULID `id`:

```http
GET /showroom/products/01JC5Y3Q8W2N4V6T9R7K1M3P5X
```

Set `showroom.lookup.products` to `slug` to use slugs instead (`GET /showroom/products/83al-ic-40-4-red-alum`). One call can choose the other key: `?by=slug` or `?by=id` over HTTP, and a `by` argument on the MCP tools. A `by` that isn't `id` or `slug` answers `422`. Identifiers are never lookup keys, so a SKU with `/` or spaces never reaches a URL. Every product resource returns `id`, `slug` and `identifier`.

## External ids

`external_ids` holds a product's ids in other systems, as a `{source: id}` map. A source is the system that owns the id, such as an ERP or a shop:

```json
{"identifier": "83AL-IC/40 4 RED ALUM", "external_ids": {"d365": "ITEM-000123", "shopify": "8812345"}}
```

- A source name is 1 to 50 lowercase letters, digits, dashes or underscores. An id is a string of at most 191 characters.
- On update, only the sources you send change, and `null` removes one.
- An id is unique per source among products: assigning one that another product holds fails at `external_ids.{source}`, and nothing is written. A product and an owner may hold the same id.
- A product holds one id per source. The `showroom_external_ids` table enforces this with a unique index on `(subject_type, subject_id, source)`, beside the unique index on `(subject_type, source, external_id)` behind the previous rule.
- Ids compare without regard to case or accents, as identifiers do.
- Each id keeps `synced_at`, the last time its source wrote or confirmed it. Every create, update or upsert that sends an id sets it to now, even when the id hasn't changed. The batch upsert also stamps the ids of every `unchanged` record, in one bulk update per batch.
- Resources and MCP tools return the map, with an empty one as `{}`. They also return `external_ids_synced_at`, a `{source: ISO-8601 timestamp|null}` map. It is read-only: sending it on create or update is refused (`422`).
- `GET /showroom/products?external[d365]=ITEM-000123` finds a product by one of them; see [Search](05-search.md).

The table follows the shape of Roster's organization links (`source`, `external_id`, `synced_at`, one id per record and source), so an app running both packages uses one vocabulary for ids in other systems.

## Batch upsert

`UpsertProductsAction` writes a batch of product records in the API (JSONL) shape, for ERP syncs of thousands of SKUs. `showroom:import-products` and `showroom:upsert-products` run it once per chunk; see [Import, export and feeds](12-impex.md).

```php
use RefactorCircus\Showroom\Domains\Product\Actions\UpsertProductsAction;
use RefactorCircus\Showroom\Domains\Product\Data\UpsertProductsData;

$result = app(UpsertProductsAction::class)->execute(UpsertProductsData::from([
    'records' => $records,
    'mode' => 'upsert',          // or create, update
    'match' => 'd365',           // or identifier (the default)
    'capture_changes' => false,  // keep the batch out of product webhooks
]));
```

- `match` pairs records with stored products by `identifier`, or by `external_ids[match]` when it names a source.
- Each record is validated with `CreateProductData` or `UpdateProductData` rules. A record that fails, or that a catalog rule refuses, is reported and never stops the others.
- A record whose sent fields (values, family, parent, owner, categories, associations, `enabled`, `external_ids`, `flags`) match the stored product is `unchanged`: no version, no index sync and no events. The only write is the `synced_at` stamp on its external ids, done in one bulk update for all the batch's unchanged records.
- New simple products are inserted in bulk. Everything else goes through `CreateProductAction` or `UpdateProductAction`, so versions, unique values, completeness and the approved → draft rule apply as they do one product at a time.
- The batch queues one index sync for every product it wrote.
- Records whose `flags` change need `assign`; see [Flags](14-flags.md#the-assign-ability).

The result, an `UpsertProductsResult`, counts `created`, `updated`, `unchanged` and `skipped` (left out by the mode). `failures` lists each refused record by its `index` in the batch, with its `identifier` and its `errors` by field.

## Unique attributes

A value of an `is_unique` attribute can be held by one product only. The check is enforced by a unique index on `showroom_product_unique_values`, so it holds under concurrent writes. Unique attributes are neither localizable nor scopable, and in a family variant they sit on the last level.

## Variants

A **family variant** describes how a family's products vary:

```json
{
  "code": "shirts_by_color_size",
  "family": "shirts",
  "levels": [
    {"axes": ["color"], "attributes": ["price"]},
    {"axes": ["size"], "attributes": ["ean", "weight"]}
  ]
}
```

- One or two levels, each with 1–5 **axes**: select, boolean or metric attributes of the family that are neither localizable nor scopable.
- Each level lists the attributes set there. Family attributes on no level are **common**, set once on the root product model.
- Levels can change only while no product model uses the variant.

Then build the tree:

| Record | Holds | Created with |
|---|---|---|
| Root product model | common values | `family_variant` |
| Sub-model (two-level variants only) | level-1 axes and attributes | `parent` = root model |
| Variant product | last-level axes and attributes | `parent` = the model at the last model level |

Every axis of a level must be filled, and siblings must differ on their axes. A variant product takes its family from the family variant, and its API `values` include everything inherited from its models. Changing a model re-indexes every variant beneath it.

## Deleting

- Deleting a product deletes its external ids and flag assignments.
- Deleting a product model deletes its sub-models and variant products.
- A family with products or product models, a family variant with product models, and an attribute placed in a family variant cannot be deleted (`409`).
- Deleting an attribute or an option purges its stored values in a queued job.

## Actions

| | |
|---|---|
| Family variants | `ListFamilyVariantsAction`, `ShowFamilyVariantAction`, `CreateFamilyVariantAction`, `UpdateFamilyVariantAction`, `DeleteFamilyVariantAction` |
| Product models | `ListProductModelsAction`, `ShowProductModelAction`, `CreateProductModelAction`, `UpdateProductModelAction`, `DeleteProductModelAction` |
| Products | `ListProductsAction` (search), `ShowProductAction`, `CreateProductAction`, `UpdateProductAction`, `DeleteProductAction`, `UpsertProductsAction` (batch) |
