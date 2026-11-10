# Bulk import readiness: SKU identifiers, slugs, external IDs, flags, batch upsert

## Goal

Make it practical for host apps to load and resync an ERP-scale catalog (millions of simple products under a vendor → series owner tree) into Showroom. This covers:

- real SKUs as identifiers
- stable, URL-safe slugs
- per-system external ids to match records on later syncs
- inheritable commercial flags (restricted, pricing only, …)
- a batch upsert that costs almost nothing when records are unchanged

## Acceptance criteria

### SKU identifiers

1. A product can be created with an identifier containing spaces and `/` (e.g. `ML20906 HPSK 630 SEC M91 M92 LC LH`, `83AL-IC/40 4 RED ALUM`). Identifiers stay unique, at most 191 characters, and cannot be changed.
2. The identifier rule comes from `showroom.products.identifier_pattern`.
   - The default rejects an empty identifier, leading or trailing whitespace, and control characters.
   - A host can set a stricter pattern, and creation then fails validation at `identifier`.

### Slugs

3. A new product gets a `slug` of `Str::slug(identifier)`. If that slug is taken, the first free `-2`, `-3`, … suffix is used, and the result is at most 191 characters.
4. A new owner gets a `slug` of `Str::slug` of its default-locale label, or of its `code` when there is no label. Collisions are handled as in AC 3, and slugs are unique across all owners.
5. A `slug` sent on create or update of a product or an owner is refused by validation. Changing a product's values, or an owner's labels, parent or code, never changes its slug.
6. Every existing product and owner has a unique slug after migrating, filled in by a migration that works in chunks.
7. Product routes bind by slug: API show/update/delete, versions, revert, transitions and the Atrium product screens. So do owner routes: API show/update/delete and the Atrium owner screens. Requesting by identifier or code at those routes answers `404`.
8. Product and owner resources, MCP tools, exports and the product search document include `slug`, and so does each owner in a product's owner chain.

### External IDs

9. Products and owners accept `external_ids` as a `{system: id}` map on create and update.
   - On update, only the systems sent change, and `null` removes a system.
   - `system` matches `^[a-z0-9_-]{1,50}$`, and `id` is a string of at most 191 characters.
10. An external id is unique per subject type and system. Assigning a `(system, id)` that another product (or owner) already holds fails validation at `external_ids.{system}`, and nothing is written. The same `(system, id)` may be held by one product and one owner.
11. Product and owner resources and MCP show/list tools return `external_ids` as a `{system: id}` map.
12. Product and owner listings filter by `external[{system}]={id}`, on both the database and Scout engines for products. Deleting a product or owner deletes its external ids.

### Flags

13. Flags are managed through List/Show/Create/Update/Delete flag actions, with matching HTTP routes (`/flags`, `/flags/{flag:code}`), MCP tools and a `FlagPolicy`.
    - A flag has `code` (unique, cannot be changed), `labels`, `applies_to` (a non-empty subset of `product` and `owner`) and `sort_order`.
14. Deleting a flag that is assigned to anything fails with a validation error. Removing a value from `applies_to` while the flag is assigned to that subject type also fails.
15. Products and owners accept `flags` (a list of flag codes) on create and update, and the list replaces the record's own set. An unknown code, or a flag whose `applies_to` doesn't include the subject, fails validation at `flags.{n}`.
16. An owner's `effective_flags` are its own flags plus the flags of every ancestor owner. A product's `effective_flags` are its own flags plus the effective flags of its owner (for variants, the root model's owner). A product or owner cannot drop an inherited flag. For example, if a vendor has `pricing_only` and its series and product have none, the product's effective flags include `pricing_only`.
17. Resources and MCP tools return `flags` (own) and `effective_flags`.
18. Product search filters by `flags[]=` on effective flags. With several flags, a product must carry all of them. This works on the database and Scout engines. The owner listing filters on owners' effective flags.
19. Changing an owner's flags, or moving an owner, queues an index sync for every product beneath it. After the sync, the search reflects the new effective flags.

### Owner types

20. An owner type can be created with its own code in `parent_types` (e.g. `vendor` with `parent_types: ["vendor"]`). An owner of that type can then be created under another owner of the same type.

### Batch upsert

21. `UpsertProductsAction` takes a list of product records in the API/JSONL shape, plus:
    - `mode`: `upsert`, `create` or `update`
    - `match`: `identifier` (default) or an external-id system name
    - `capture_changes`: default `true`

    Records are matched to existing products by identifier, or by `external_ids[match]`.
22. Each record is validated with the same rules as `CreateProductData` or `UpdateProductData`.
    - An invalid record, or one refused by a domain guard, is reported in the result with its index, its identifier and the errors, and the other records are still written.
    - The result counts `created`, `updated`, `unchanged`, `skipped` (mode excluded) and `failed`.
23. A record whose sent fields equal the stored product's is `unchanged`: it writes no rows, records no version, queues no index sync and fires no product write events.
    - "Sent fields" means values, family, parent, owner, categories, associations, enabled, external_ids and flags.
    - Re-upserting an unchanged batch of N records runs a number of queries that does not grow with N.
24. A created product records exactly one `created` version. An updated product records one `updated` version, and only when something changed.
    - Unique values, completeness and the approved → draft rule apply exactly as in `CreateProductAction` and `UpdateProductAction`.
25. A batch queues one index sync covering all of its written products, not one per product. A batch of N new simple products runs a number of queries that grows sub-linearly with N; the bound is measured in the test.
26. With `capture_changes: false`, the batch's writes are not recorded for product webhooks. With the default, they are recorded as for any product write.
27. `showroom:upsert-products` and `showroom:import-products` process each chunk through `UpsertProductsAction`, and accept `capture_changes`. Their run results keep the counts `total`, `succeeded` and `failed`, add `unchanged`, and keep per-row failure reasons on the batch items.
28. Core code (outside `src/Impex`) does not reference Impex classes. The arch test enforces this.

## Design

### Identifier rule

- `CreateProductData::rules()` reads `config('showroom.products.identifier_pattern')`, cast to a string with the default `'/^[^\s\p{C}](?:[^\p{C}]{0,189}[^\s\p{C}])?$/u'`, and adds it to `max:191` and `unique`.
- The config file gets a `products` banner section documenting the rule.
- Inputs that reference products (`parent`, associations, filters, MCP tool arguments) keep using the identifier.

### Slugs

- **Migration `2026_11_10_000001_add_slugs_to_showroom_products_and_owners`:**
  - Adds a nullable `slug` (191) to `products` and `owners`.
  - Backfills in chunks of 1,000 through the shared slug generator.
  - Then makes the column non-null and unique.
- **Generator:** a `Support\Slugs` service with `for(string $table, string $source): string` and a batched `forMany(string $table, array $sources): array`.
  - The batched form loads the taken slugs that share each base in one query and assigns suffixes in memory.
  - On a unique-index race it retries once with a fresh lookup.
- **Assignment:** set by `CreateProductAction`, `CreateOwnerAction` and `UpsertProductsAction`. `slug` is `prohibited` in the create and update Data rules.
- **Routes:** change to `{product:slug}` and `{owner:slug}` in `Domains/Product/routes.php`, `Domains/Workflow/routes.php`, `Domains/Owner/routes.php` and `Atrium/ShowroomPlugin.php`. Atrium links use the slug.
- **Resources:** `ProductResource` and `OwnerResource` add `slug`. `ProductDocument` adds `slug`, and adds `owner_slugs` to the owner chain.

### External IDs

- **Location:** a new domain `Domains/ExternalId`.
- **Table:** `ExternalIdModel` on `showroom.tables.external_ids` (`showroom_external_ids`):
  - columns: `id` ulid, `subject_type` string(50) (morph alias `product` or `owner`), `subject_id` ulid, `system` string(50), `external_id` string(191), timestamps
  - unique `(subject_type, system, external_id)`, index `(subject_type, subject_id)`
- **Writing:** an `ExternalIds` service (scoped) with `write(Model $subject, array $map)`, `load(Collection $subjects)` (eager, one query) and `find(string $type, string $system, array $ids): array<id, subject_id>`.
  - Product and owner create and update call `write()` inside their transaction.
  - A `(system, id)` held by another subject throws `ValidationException` at `external_ids.{system}`.
- **Relations:** `ProductModel::externalIds()` and `OwnerModel::externalIds()` are `morphMany` relations, deleted with the subject.
- **Filters:** `ListProductsData` and `ListOwnersData` gain `external: array<string, string>`. `DatabaseEngine` joins the table; `ScoutEngine` filters on the document's `external_ids` field, a list of `"{system}:{id}"` strings.
- **Policy:** no separate policy. External ids are written and read only through their subject's actions, so the subject's policy covers them.

### Flags

- **Location:** a new domain `Domains/Flag`, holding `FlagModel` (`showroom.tables.flags`), `FlagPolicy` (`showroom.policies.flag`), CRUD actions with Data and action-event pairs, requests, `FlagController`, `FlagResource`, routes and MCP tools. The MCP tools mirror `Domains/Owner`.
- **Tables:**
  - `showroom_flags`: `id` ulid, `code` unique (100), `labels` json, `applies_to` json, `sort_order`, timestamps
  - `showroom_flag_assignments`: `flag_id` FK (restrict on delete), `subject_type`, `subject_id`, primary key on all three, index `(subject_type, subject_id)`
- **Writing:** a `Flags` service with `write(Model $subject, list<string> $codes)`, `own(Collection)` and `effective(Collection)`.
  - **For owners**, `effective()` gathers each owner's ancestor ids from `path` and loads the assignments of all of those owners in one query.
  - **For products**, it resolves the owner (the root model's owner for variants) and reuses the owner result.
- **Search:**
  - `ProductDocument` writes `flags` as the product's effective flags.
  - `DatabaseEngine` resolves `flags[]` as: the product has an own assignment, or its owner (or the root model's owner) has the flag through its subtree. That is, the owners carrying the flag and every owner beneath them (`subtreeOf`), unioned, then matched with `whereIn`, as the `owner` filter does today.
- **Reindexing:** `UpdateOwnerAction` already calls `ProductIndex::queueWithin(SyncScope::Owner, …)` when an owner moves. It also calls it when the owner's flags change.
- **Guards:**
  - `DeleteFlagAction` refuses while assignments exist.
  - `UpdateFlagAction` refuses to remove a subject type from `applies_to` while assignments of that type exist.

### Owner types

- `CreateOwnerTypeData`: `parent_types.*` passes when it equals the incoming `code` or exists. The action creates the type, then resolves `parent_types` (including itself).

### Batch upsert

- **Input:** `Domains/Product/Actions/UpsertProductsAction` takes `UpsertProductsData`:
  - `records: list<array>`
  - `mode: UpsertMode` (enum: `Upsert`, `Create`, `Update`)
  - `match: string = 'identifier'`
  - `captureChanges: bool = true`
- **Result:** `UpsertProductsResult` (Data):
  - counts `created`, `updated`, `unchanged`, `skipped`
  - `failures: list<array{index:int, identifier:?string, errors:array<string, list<string>>}>`
- **Events:** `ProductsUpsertingActionEvent` / `ProductsUpsertedActionEvent`.
- **Flow of one call:**
  1. Validate each record against `CreateProductData::rules()` or `UpdateProductData::rules()`, chosen by whether it matched. Invalid records go to `failures`.
  2. Load the matched products in one query, by identifier or through `ExternalIds::find()`. Eager-load values, family, parent, owner, categories, associations, external ids and own flags.
  3. Preview: normalize each record's sent fields and the stored product into the same shape and compare them in memory. Equal records count as `unchanged`.
  4. Write the changed and new records inside `ProductIndex::deferring()`, so one index sync is queued for the batch.
     - New records whose fields are all simple (no associations, no assets) are bulk-inserted: products, slugs (`Slugs::forMany`), unique values, external ids, flag assignments and one `created` version per product, in one transaction per chunk.
     - Every other record goes through `CreateProductAction` or `UpdateProductAction` in its own transaction, so domain guards and per-record failure isolation stay exact.
  5. With `captureChanges: false`, the writes run inside `ProductChangeCapture::suppress(fn () => …)`. That is a core, scoped service; `CaptureProductChanges` in `src/Impex` checks it before recording.
- **Impex integration:**
  - `ImportProductRow` is replaced by `Impex/Actions/UpsertProductChunk`, which calls `UpsertProductsAction` once per chunk.
  - `InlineProductSource` and `FileProductSource` yield chunks.
  - `UpsertProductsFlow` and `ImportProductsFlow` accept `capture_changes` and pass it through.
  - Failures are stored per row on the batch item.
- **Authorization:** the HTTP and MCP start actions already authorize. The action itself runs as the caller, like the existing per-row import.

### Config

- `products.identifier_pattern`
- `impex.chunk`: kept, and used for upsert chunks
- `tables.external_ids`, `tables.flags`, `tables.flag_assignments`
- `policies.flag`

## Out of scope

- Owner values and metadata: logos, descriptions, owner attributes.
- Keeping ERP-owned changes from demoting `approved` products to `draft`. That's needed before a D365 sync.
- Configurable workflow statuses.
- External ids and flags on product models.
- HTTP and MCP endpoints for the batch upsert itself.
- Flag negation (a child clearing an inherited flag).
- Any host-app flow, such as `core:catalog`.

## Risks and open questions

- **Breaking change to routes:** clients that call product URLs by identifier, or owner URLs by code, break. Should identifier and code be accepted as a fallback for one release? Today the spec answers `404` (AC 7).
- **Bulk insert vs actions:** the bulk-insert path for new simple products duplicates part of `CreateProductAction`. The risk is drift from the per-record behaviour. Mitigation: a shared write step, and tests that compare a product created through the bulk path with one created through the action.
- **Scout mappings:** the new `slug`, `external_ids`, `flags` and `owner_slugs` fields need the index settings updated and `showroom:search:reindex` run after upgrade.
- **Backfill time:** generating slugs for millions of existing rows is slow. Showroom installs today are small, but the migration has to work in chunks and be resumable.
- **Effective flags cost:** a deep owner chain makes effective-flag resolution walk the path. Owner counts are small, about 3k, so it's resolved per batch and not cached. Revisit if owners grow.
- **Slug truncation:** a 191-character identifier plus a suffix needs truncation before the suffix. Covered by AC 3.
