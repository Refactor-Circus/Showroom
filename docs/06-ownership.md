# Ownership

Products belong to owners, and owners form chains of any shape and depth:

```
vendor → series → product
vendor → product
vendor → vendor → series → product
manufacturer → vendor → product
```

## Owner types

Owner types are defined at runtime, each with the rules for where its owners may sit:

```json
{"code": "series", "parent_types": ["vendor"], "can_be_root": false, "owns_products": true}
```

| Field | Meaning |
|---|---|
| `parent_types` | Owner types allowed as parent. `null` allows any type; `[]` allows none. |
| `can_be_root` | Whether an owner of this type may have no parent. Default `true`. |
| `owns_products` | Whether products and product models may be assigned to it. Default `true`. |

Rules are checked when owners are created or moved; changing them later does not rewrite owners already placed. An owner type with owners cannot be deleted.

An owner type may name itself as a parent, for chains such as `vendor → vendor → series`: create `vendor` with `parent_types: ["vendor"]`, then place a vendor under another vendor. An unknown parent type is still refused.

## Owners

```json
{"code": "classic", "type": "series", "parent": "acme", "labels": {"en": "Classic"}, "enabled": true, "external_ids": {"d365": "V-0042"}, "flags": ["pricing_only"]}
```

- `code` is unique across all owners and never changes; neither does `type`.
- Sending `parent` on update **moves** the owner with everything beneath it. Moving an owner under itself or its own descendant is refused.
- `GET /showroom/owners/{owner}` returns its `chain` of codes, root first: `["globex", "acme", "classic"]`.
- List by `type`, direct children of a `parent`, everything `under` an owner at any depth, or `roots`. Filter by `external[{source}]={id}` and by `flags[]=` (effective flags, inherited ones included).
- An owner with child owners, products or product models cannot be deleted. Deleting an owner deletes its external ids and flag assignments.

Each owner stores a materialized path, so "everything beneath Acme" is a single prefix query however deep the chain.

### Slugs

Every owner gets a `slug` when it's created, from the first of these that slugifies to something: its default-locale label, its `code`, or the word `owner`. `Classic` gives `classic`. Slugs are unique across all owners, take the first free `-2`, `-3`, … suffix on a collision, and are suffixed when they would read as a route segment, as product slugs are ([Slugs](04-products.md#slugs)). A slug never changes, even when the owner's labels, parent or code do. `slug` is read-only, so sending it on create or update is refused.

### Looking up an owner

API paths, Atrium links, MCP `owner` arguments and the show, update and delete actions find an owner by its ULID `id`. Set `showroom.lookup.owners` to `slug` to use slugs instead, or pick the key for one call with `?by=id` or `?by=slug` over HTTP and a `by` argument on the MCP tools. An owner's `code` is never a lookup key. It stays the reference for `parent`, a product's `owner`, and the `owner`, `parent` and `under` filters. Every owner resource returns `id`, `slug` and `code`.

### Enabled

Owners are `enabled` (default `true`) or not, and switching one off switches off everything beneath it. A product is **effectively enabled** only when the product itself and every owner of its chain are enabled. Its chain is its effective owner — a variant's root model's owner — and that owner's ancestors. Disabling a vendor leaves each product's own `enabled` as it is; their read-only `effective_enabled` turns `false`.

- Send `enabled` on create or update (API, MCP `create-owner-tool` and `update-owner-tool`, `CreateOwnerData`, `UpdateOwnerData`); left out on update, it's kept. Owner resources return it.
- Changing it re-indexes every product beneath the owner, as a flag change does.
- Products return `effective_enabled` from list, show, create and update (API and MCP), computed for a whole page with one query of the owners in its chains. The search document carries it, and listings filter on it: `GET /showroom/products?effective_enabled=1`. See [Search](05-search.md).
- The Atrium owner screen shows whether the owner is enabled, and its edit form switches it.

### External ids and flags

Owners take `external_ids` (a `{source: id}` map) as products do, and return `external_ids_synced_at` beside it; see [External ids](04-products.md#external-ids). An id is unique per source among owners, an owner holds one id per source, and an owner may share an id with a product.

Owners take `flags` too, and pass them down. An owner's flags apply to every owner and product beneath it, and nothing beneath can drop them. Changing an owner's flags needs the `assign` ability and re-indexes every product beneath it. See [Flags](14-flags.md).

## Batch upsert

`UpsertOwnersAction` writes a batch of owner records in the API shape, for ERP syncs of vendors and series. It works like the [product batch upsert](04-products.md#batch-upsert), one owner at a time.

```php
use RefactorCircus\Showroom\Domains\Owner\Actions\UpsertOwnersAction;
use RefactorCircus\Showroom\Domains\Owner\Data\UpsertOwnersData;

$result = app(UpsertOwnersAction::class)->execute(UpsertOwnersData::from([
    'records' => $records,  // [{"code": "classic", "type": "series", "parent": "acme", ...}, ...]
    'mode' => 'upsert',     // or create, update
    'match' => 'd365',      // or code (the default)
]));
```

- `match` pairs records with stored owners by `code`, or by `external_ids[match]` when it names a source. In `create` mode a matched record is skipped; in `update` mode an unmatched one is.
- Each record is validated with `CreateOwnerData` or `UpdateOwnerData` rules. A matched record can't change the owner's `code` or `type`. A field the record leaves out keeps its stored value, and `"parent": null` moves the owner to the root.
- Two records with the same `code`, or the same matched external id, can't both be written. The first one keeps it, whatever becomes of it, and the later one fails at `code`.
- A record's `parent` can name an owner stored before the batch, or one an earlier record created. Send parents before their children: a parent later in the batch is refused as unknown. Once a record moves an owner, later records see its new place, and that of everything beneath it.
- A record whose sent fields (`labels`, `parent`, `enabled`, `external_ids`, `flags`) match the stored owner is `unchanged`. Nothing is written for it, and no event fires. The only write is the `synced_at` stamp on its external ids, done in one bulk update for all the batch's unchanged records.
- Every other record is written in its own transaction, with the placement, slug, external id and flag rules of `CreateOwnerAction` and `UpdateOwnerAction`, and fires their events. A record that fails, whether a rule or the database refuses it, rolls back alone and never stops the others.
- The products beneath every owner that moved, was enabled or disabled, or changed its flags get one index sync for the whole batch.
- Records whose `flags` change need `assign`; see [Flags](14-flags.md#the-assign-ability). The batch checks nothing else per record: its caller authorizes creating and updating owners. A queued import has no signed-in user to hold `assign`: run it inside `Flags::trusted()` ([Trusted system syncs](14-flags.md#trusted-system-syncs)).
- The action expects at most `showroom.impex.chunk` records per call, and reads what it needs in lookups of that size. Send larger batches in chunks.

The result, an `UpsertOwnersResult`, counts `created`, `updated`, `unchanged` and `skipped`. `failures` lists each refused record by its `index` in the batch, with its `code` and its `errors` by field. The batch fires `OwnersUpsertingActionEvent` before and `OwnersUpsertedActionEvent` after, with the result.

## Assigning products

Set `owner` on a simple product or a root product model, naming the deepest owner in the chain — the series, not the vendor. Sub-models and variant products take their root model's owner and cannot set their own; the API returns it as their `owner`.

## Searching by owner

`GET /showroom/products?owner=acme` finds every product owned by Acme **or anything beneath it**, on every search engine. The indexed document carries `owner`, `owners` (the whole chain of codes) and `owner_slugs` (the same chain by slug), and moving an owner, enabling or disabling it, or changing its flags, re-indexes the products beneath it.

## Actions

`ListOwnerTypesAction`, `ShowOwnerTypeAction`, `CreateOwnerTypeAction`, `UpdateOwnerTypeAction`, `DeleteOwnerTypeAction`, `ListOwnersAction`, `ShowOwnerAction`, `CreateOwnerAction`, `UpdateOwnerAction`, `DeleteOwnerAction`, `UpsertOwnersAction`.
