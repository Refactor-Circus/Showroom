# Bulk import readiness: SKU identifiers, slugs, external IDs, flags, batch upsert

## Goal

Make it practical for host apps to load and resync an ERP-scale catalog (millions of simple products under a vendor → series owner tree) into Showroom. This covers:

- real SKUs as identifiers
- stable, URL-safe slugs
- per-system external ids to match records on later syncs
- inheritable commercial flags (restricted, pricing only, …)
- a batch upsert that costs almost nothing when records are unchanged
- record lookup by ULID `id` by default, configurable per subject and per call
- Atrium screens for flag definitions, and for slug, external ids and flags on products and owners

## Acceptance criteria

### SKU identifiers

1. A product can be created with an identifier containing spaces and `/` (e.g. `ML20906 HPSK 630 SEC M91 M92 LC LH`, `83AL-IC/40 4 RED ALUM`). Identifiers stay unique, at most 191 characters, and cannot be changed.
   - Identifiers are never a lookup key (D-010), so a `/` or space in a SKU never reaches a URL. A product with the identifier `83AL-IC/40 4 RED ALUM` is created, then fetched by its `id` and by its `slug`.
2. The identifier rule comes from `showroom.products.identifier_pattern`.
   - The default rejects an empty identifier, leading or trailing whitespace, and control characters.
   - A host can set a stricter pattern, and creation then fails validation at `identifier`. A test sets a stricter pattern with `config()->set` and sees creation fail at `identifier`.
   - Boundary dataset: accepts `A`, `A  B` (interior double space) and a 191-character identifier. Rejects `''`, ` A`, `A `, `"A\tB"` and a 192-character identifier.
   - A pattern that does not compile fails fast with a clear exception, not a `preg_match` warning per request.

### Slugs

3. A new product gets a `slug` of `Str::slug(identifier)`. If that slug is taken, the first free `-2`, `-3`, … suffix is used, and the result is at most 191 characters.
   - `A/B` then `A B` give `a-b`, `a-b-2`.
   - A collided 191-character identifier gives a slug of exactly 191 characters ending in `-2`.
   - Two records with the same base in one `Slugs::forMany` call get `-2` and `-3` with no gaps.
   - Input `a`, `a`, `a-2` (in one batch) gives three distinct slugs: existing slugs and slugs already assigned in the batch both count as taken.
4. A new owner gets a `slug` of `Str::slug` of its default-locale label. When there is no label, or the label slugifies to an empty string, it uses `Str::slug` of its `code`. When that is empty too, it uses `owner`. Collisions are handled as in AC 3, and slugs are unique across all owners.
5. A `slug` (or, for resources, `effective_flags`) sent on create or update of a product or an owner is refused by validation. A test posts a full resource body back and sees the read-only keys named in the errors. Changing a product's values, or an owner's labels, parent or code, never changes its slug.
6. Every existing product and owner has a unique slug after migrating, filled in by a migration that works in chunks.
   - The backfill selects only rows with `slug IS NULL`, in key order, so a re-run after a partial backfill skips rows already filled.
   - Tests: chunk size forced to 2 over 5 rows, and a re-run after a partial backfill.
   - The backfill uses the same generator and the same empty-source and reserved-word rules as AC 7. A test backfills rows whose source slugifies to empty.
7. Empty and reserved slugs follow one rule set, shared by creation, `Slugs::forMany` and the backfill.
   - A source that slugifies to an empty string uses the base `product` (products) or `owner` (owners), then the usual suffix rule. Dataset: identifiers `///` and `!!!` give `product` then `product-2`; an owner with no label and the code `///` gives `owner`.
   - Owner source order is: default-locale label slug, then `code` slug, then `owner`.
   - A base that equals a reserved segment gets the suffix, so `versions` becomes `versions-2`. The reserved list is `Slugs::RESERVED`, derived from the literal segments of the product and owner routes in `route:list` (for example `versions`, `revert`, `transitions`). A test asserts the constant covers every literal segment that follows a `{product}` or `{owner}` parameter.
   - A reserved word at 191 characters still fits in 191 characters after suffixing.
8. Products and owners are looked up by their ULID `id` by default. A dataset over every route in `php artisan route:list` that takes a `{product}` or `{owner}` parameter (API show/update/delete, versions, revert, transitions in Workflow, and the Atrium product and owner screens) asserts that `id` binds under the default config and that the identifier, code or slug answers `404`.
   - Flag routes (`/flags/{flag:code}`) are not covered by D-001 and keep `code` binding.
9. `showroom.lookup.products` and `showroom.lookup.owners` (each `id` or `slug`; default `id`) change the key used by route binding, Atrium links, MCP tool arguments and the show, update, delete, transition, versions and revert actions. A dataset over the same routes as AC 8, run once per configured key, asserts that the configured key binds and the others answer `404`.
10. The per-call override `by` selects the key for that call. HTTP takes `?by=`; the MCP show, update, delete, transition, versions and revert tools take a `by` argument. A call with `by` binds by that key whatever the config says. An invalid `by` (not in the enum, or a key the subject lacks, such as `by=identifier` or `by=code`) answers `422` over HTTP and a validation error over MCP, and never reaches the action.
11. An invalid value for `showroom.lookup.products` or `showroom.lookup.owners` throws a clear exception when the app boots. A test boots the provider with `bogus` and expects the exception.

12. Product and owner resources, MCP tools, exports and the product search document include `slug`, and so does each owner in a product's owner chain. Export columns and MCP outputs carry `slug` by name.

### External IDs

13. Products and owners accept `external_ids` as a `{system: id}` map on create and update.
   - On update, only the systems sent change, and `null` removes a system.
   - `system` matches `^[a-z0-9_-]{1,50}$`, and `id` is a string of at most 191 characters.
14. An external id is unique per subject type and system. Assigning a `(system, id)` that another product (or owner) already holds fails validation at `external_ids.{system}`, and nothing is written (slug, values and other external ids stay untouched). Inside one batch, a second record holding the same `(system, id)` fails at `external_ids.{system}` and the first persists. The same `(system, id)` may be held by one product and one owner.
15. Product and owner resources and MCP show/list tools return `external_ids` as a `{system: id}` map. An empty map serialises as an object (`{}`), not a list (`[]`).
16. Product and owner listings filter by `external[{system}]={id}`, on both the database and Scout engines for products. Deleting a product or owner deletes its external ids. External id values containing `"`, `\` and ` OR ` filter correctly on the Scout engine (values are quoted and escaped, never interpolated raw).

### Flags

17. Flags are managed through List/Show/Create/Update/Delete flag actions, with matching HTTP routes (`/flags`, `/flags/{flag:code}`), MCP tools and a `FlagPolicy`.
    - A flag has `code` (unique, cannot be changed), `labels`, `applies_to` (a non-empty subset of `product` and `owner`) and `sort_order`.
18. Deleting a flag that is assigned to anything fails with a validation error. Removing a value from `applies_to` while the flag is assigned to that subject type also fails.
19. Products and owners accept `flags` (a list of flag codes) on create and update, and the list replaces the record's own set. An unknown code, or a flag whose `applies_to` doesn't include the subject, fails validation at `flags.{n}`.
   - Changing a record's `flags` also requires the `assign` ability (AC 37).
20. An owner's `effective_flags` are its own flags plus the flags of every ancestor owner. A product's `effective_flags` are its own flags plus the effective flags of its owner (for variants, the root model's owner). A product or owner cannot drop an inherited flag. For example, if a vendor has `pricing_only` and its series and product have none, the product's effective flags include `pricing_only`.
21. Resources and MCP tools return `flags` (own) and `effective_flags`.
22. Product search filters by `flags[]=` on effective flags. With several flags, a product must carry all of them. This works on the database and Scout engines, tested with one shared dataset run against both (own flags, owner-inherited flags, root-model-owner variants, multi-flag AND). The Scout engine is tested through its test driver or a fake. Flag codes are escaped in Scout filters as external ids are. The owner listing filters on owners' effective flags.
23. Changing an owner's flags, or moving an owner, queues an index sync for every product beneath it.
    - Queue-fake test: `ProductIndex::queueWithin(SyncScope::Owner, …)` is queued on a flag change and on a move, and not when the flags are sent unchanged.
    - Search test: after the sync, the filter reflects the new effective flags, including for a grandchild owner.

### Owner types

24. An owner type can be created with its own code in `parent_types` (e.g. `vendor` with `parent_types: ["vendor"]`). An owner of that type can then be created under another owner of the same type.

### Batch upsert

25. `UpsertProductsAction` takes a list of product records in the API/JSONL shape, plus:
    - `mode`: `upsert`, `create` or `update`
    - `match`: `identifier` (default) or an external-id system name
    - `capture_changes`: default `true`

    Records are matched to existing products by identifier, or by `external_ids[match]`.
26. Each record is validated with the same rules as `CreateProductData` or `UpdateProductData`.
    - A failing record never affects its neighbours in the chunk: a mixed chunk of valid, invalid and guard-refused records persists every good one.
   - An invalid record, or one refused by a domain guard, is reported in the result with its index, its identifier and the errors, and the other records are still written.
    - The result counts `created`, `updated`, `unchanged`, `skipped` (mode excluded) and `failed`.
27. A record whose sent fields equal the stored product's is `unchanged`: it writes no rows, records no version, queues no index sync and fires no product write events.
    - Observables: row counts and `updated_at` unchanged, no new version rows, no index job on the Bus/Queue fake, and no `ProductCreating`/`ProductUpdating` action events or Eloquent `saved` events on the Event fake.
    - Each sent-field category (value, family, parent, owner, category, association, enabled, external_ids, flags), when changed, makes the record `updated` and not `unchanged`.
    - "Sent fields" means values, family, parent, owner, categories, associations, enabled, external_ids and flags.
    - Re-upserting an unchanged batch runs a query count that does not grow with N: the test runs batches of 10 and 100 records and asserts the count is at most one fixed constant, which both runs respect. The implementer sets the constant.
28. A created product records exactly one `created` version. An updated product records one `updated` version, and only when something changed.
    - Unique values, completeness and the approved → draft rule apply exactly as in `CreateProductAction` and `UpdateProductAction`.
29. A batch queues one index sync covering all of its written products, not one per product. A batch of N new simple products runs a sub-linear number of queries: the test runs batches of 10 and 100 new simple products, asserts both counts are at most one fixed constant set by the implementer, and asserts the count at 100 is below 100, so a per-record query fails it.
30. With `capture_changes: false`, the batch's writes are not recorded for product webhooks. With the default, they are recorded as for any product write.
    - An Impex-layer test shows `CaptureProductChanges` records nothing while `suppress()` is active and records by default. The core `suppress()` state test is AC 35; the split between the two layers matches the arch rule in AC 32.
31. `showroom:upsert-products` and `showroom:import-products` process each chunk through `UpsertProductsAction`, and accept `capture_changes`. Their run results keep the counts `total`, `succeeded` and `failed`, add `unchanged`, and keep per-row failure reasons on the batch items.
    - The CLI option is `--capture-changes` / `--no-capture-changes`.
    - A failure is reported with its global source row index, not its index inside the chunk. A test fails a row in the second chunk.
32. Webhook suppression (AC 30) must not make core depend on Impex: core code (outside `src/Impex`) does not reference Impex classes. The arch test in `tests/Arch` enforces this with `expect('RefactorCircus\\Showroom')->not->toUse('RefactorCircus\\Impex')->ignoring('RefactorCircus\\Showroom\\Impex')`.

### Cleanup and engine parity

33. Deleting a product or owner deletes its flag assignments. `DeleteFlagAction` ignores assignments whose subject no longer exists, so orphans never block flag deletion. Tested on the owner-delete path.
34. `ScoutEngine`'s index settings include `slug`, `external_ids`, `flags` and `owner_slugs`, and a test asserts the settings list them.
35. `ProductChangeCapture::suppress()` restores the previous state in a `finally`. A core unit test shows the state is set inside the callback, restored after, restored when the callback throws, and does not leak between requests (scoped binding).

### Case, assignment, parity and Atrium

36. Identifiers and external ids compare under one key normalization, `Support\KeyNormalizer::normalize`: case folding plus accent stripping, matching the database default `utf8mb4` `_ai_ci` collation. Every in-memory match and every Scout document or filter value uses it.
    - `a` and `A` identifiers collide in validation, at `identifier`.
    - An upsert with an identifier that differs only by case or accent matches the stored product and does not create a second one.
    - Inside one batch, a duplicate `(system, id)` that differs only by case fails at `external_ids.{system}` and the first persists.
    - The Scout `external_ids` and `flags` filters match case-insensitively: the document stores normalized values and the filter normalizes its input.
    - The tests do not rely on the test database's collation: normalization is asserted directly, and DB-backed cases run on a column set to a case-insensitive collation (a `NOCASE` collation on SQLite, or MySQL).
37. `FlagPolicy` has an `assign` ability. Create, update and upsert of a product or an owner that change `flags` require `assign` in addition to update permission. A request that sends the flags unchanged does not need it.
    - In the upsert flow, `assign` is checked per record in the validate step, before the unchanged preview and before the bulk path. A refusal is a guard-refused failure at `flags`, and the other records are still written.
    - Tests cover HTTP, MCP, Atrium and a mixed upsert chunk, for products and for owners.
38. A product created through the bulk path equals one created through `CreateProductAction`, with ids and timestamps ignored, across rows, `slug`, the `created` version, unique values, external ids and flag assignments.
39. A mixed chunk of valid, invalid and guard-refused records (including an `assign` refusal) persists every good record. A forced write failure inside the bulk chunk falls back to the per-record path and gives the same result as AC 38 and the same persisted set.
40. Atrium ships a flag-definitions screen (list, create, edit, delete) that shows the refusal message from AC 18 when a delete is refused.
41. The Atrium product and owner screens show `slug` read-only, an editable `external_ids` map, an editable own-flags field, and `effective_flags` read-only with inherited flags marked. The own-flags field is hidden or disabled without `assign`. Tests cover each element and the missing-`assign` case.

## Design

### Identifier rule

- `CreateProductData::rules()` reads `config('showroom.products.identifier_pattern')`, cast to a string with the default `'/^[^\s\p{C}](?:[^\p{C}]{0,189}[^\s\p{C}])?$/u'`, and adds it to `max:191` and `unique`. The pattern is passed as an array rule (`Rule::` form), not a pipe string, so a `|` in it is safe. A pattern that fails to compile throws a clear exception.
- The config file gets a `products` banner section documenting the rule.
- Inputs that reference products (`parent`, associations, filters, MCP tool arguments) keep using the identifier.

### Slugs

- **Migration `2026_11_10_000001_add_slugs_to_showroom_products_and_owners`:**
  - Adds a nullable `slug` (191) to `products` and `owners`.
  - Backfills in chunks of 1,000 through the shared slug generator.
  - Then, only once zero `NULL` rows remain, makes the column non-null and unique. The backfill is idempotent: it selects `slug IS NULL` rows in key order.
  - On MySQL the unique index on a large table (millions of rows) holds a long lock. Document the expected duration for large installs in the upgrade notes.
- **Generator:** a `Support\Slugs` service with `for(string $table, string $source): string` and a batched `forMany(string $table, array $sources): array`.
  - The batched form loads the taken slugs that share each base in one query and assigns suffixes in memory.
  - Existing slugs and slugs already assigned within the call count as taken, so an in-memory suffix never collides with a later row's natural slug.
  - On a unique-index race it retries once with a fresh lookup.
  - An empty base becomes `product` or `owner`, and a base in `Slugs::RESERVED` is suffixed (AC 7). The owner source order is label, then code, then `owner`.
- **Assignment:** set by `CreateProductAction`, `CreateOwnerAction` and `UpsertProductsAction`. `slug` is `prohibited` in the create and update Data rules.
- **Routes:** route binding follows the configured lookup key (see Record lookup) in `Domains/Product/routes.php`, `Domains/Workflow/routes.php`, `Domains/Owner/routes.php` and `Atrium/ShowroomPlugin.php`. Atrium links use the configured key. `/flags/{flag:code}` is unchanged.
- **Resources:** `ProductResource` and `OwnerResource` add `slug`. `ProductDocument` adds `slug`, and adds `owner_slugs` to the owner chain.

### Record lookup (D-001)

- **Resolver:** one `Support\RecordLookup` service, used by route binding, MCP tools and the actions. `resolve(string $subject, string $value, ?LookupKey $by = null)` picks the key from `$by`, else from `showroom.lookup.products` or `showroom.lookup.owners`.
- **Enum:** `LookupKey` with cases `Id` and `Slug`, for products and owners alike. Identifier and code are never lookup keys (D-010); they stay input references only.
- **Route binding:** implicit `{product}` and `{owner}` bindings use explicit binders that call the resolver. `?by=` is read from the request inside the binder, because Laravel binds the model before the action runs.
- **Actions that accept `by`:** show, update, delete, transition, versions and revert, for products; show, update and delete for owners. An invalid `by` answers `422` (HTTP) or a validation error (MCP) before the action runs.
- **Boot check:** the service provider validates both config values and throws a clear exception on an invalid one.
- **Not covered:** flags keep `code` binding. Inputs that reference other records (`parent`, `owner`, associations, filters) keep using identifier or code.
- **Resources** include `id`, `slug`, and `identifier` or `code`.
- **Identifiers with `/`:** never in URLs, because identifier is not a lookup key (D-010).
- **Standards deviation:** `models/stored-identifiers` and `http/resources` say to reference records by slug, never by internal id. Implementation adds a row to `docs/agent/standards-feedback.md` citing D-001.

### External IDs

- **Location:** a new domain `Domains/ExternalId`.
- **Table:** `ExternalIdModel` on `showroom.tables.external_ids` (`showroom_external_ids`):
  - columns: `id` ulid, `subject_type` string(50) (morph alias `product` or `owner`), `subject_id` ulid, `system` string(50), `external_id` string(191), timestamps
  - unique `(subject_type, system, external_id)`, index `(subject_type, subject_id)`
- **Writing:** an `ExternalIds` service (scoped) with `write(Model $subject, array $map)`, `load(Collection $subjects)` (eager, one query) and `find(string $type, string $system, array $ids): array<id, subject_id>`.
  - Product and owner create and update call `write()` inside their transaction.
  - A `(system, id)` held by another subject throws `ValidationException` at `external_ids.{system}`.
- **Relations:** `ProductModel::externalIds()` and `OwnerModel::externalIds()` are `morphMany` relations, deleted with the subject.
- **Filters:** `ListProductsData` and `ListOwnersData` gain `external: array<string, string>`. `DatabaseEngine` joins the table; `ScoutEngine` filters on the document's `external_ids` field, a list of `"{system}:{id}"` strings. Values are quoted and escaped by the engine when building the filter expression.
- **Policy:** no separate policy. External ids are written and read only through their subject's actions, so the subject's policy covers them.

### Flags

- **Location:** a new domain `Domains/Flag`, holding `FlagModel` (`showroom.tables.flags`), `FlagPolicy` (`showroom.policies.flag`, abilities viewAny, view, create, update, delete and `assign`), CRUD actions with Data and action-event pairs, requests, `FlagController`, `FlagResource`, routes and MCP tools. The MCP tools mirror `Domains/Owner`.
- **Tables:**
  - `showroom_flags`: `id` ulid, `code` unique (100), `labels` json, `applies_to` json, `sort_order`, timestamps
  - `showroom_flag_assignments`: `flag_id` FK (restrict on delete), `subject_type`, `subject_id`, primary key on all three, index `(subject_type, subject_id)`
- **Writing:** a `Flags` service with `write(Model $subject, list<string> $codes)`, `own(Collection)` and `effective(Collection)`.
  - **For owners**, `effective()` gathers each owner's ancestor ids from `path` and loads the assignments of all of those owners in one query.
  - **For products**, it resolves the owner (the root model's owner for variants) and reuses the owner result.
- **Search:**
  - `ProductDocument` writes `flags` as the product's effective flags.
  - `DatabaseEngine` resolves `flags[]` as: the product has an own assignment, or its owner (or the root model's owner) has the flag through its subtree. That is, the owners carrying the flag and every owner beneath them (`subtreeOf`), unioned, then matched with `whereIn`, as the `owner` filter does today.
- **Reindexing:** `UpdateOwnerAction` already calls `ProductIndex::queueWithin(SyncScope::Owner, …)` when an owner moves. It also calls it when the owner's flags change. This is a known cost: a flag change on a top owner queues a sync for every product beneath it (about 2M for the largest vendor). It dispatches the single `QueueProductSyncs` job, with no in-request walk. `ProductIndex::sync` bumps `changed_at` as it does today, so feed and webhook consumers see those products as changed.
- **Assignment permission (D-004):** `Flags::write()` is only called after the caller passes `assign`, checked in `CreateProductAction`, `UpdateProductAction`, `CreateOwnerAction`, `UpdateOwnerAction` and the upsert validate step when the sent `flags` differ from the stored set.
- **Guards:**
  - `DeleteFlagAction` refuses while assignments exist.
  - `DeleteProductAction`/`DeleteOwnerAction` (and the model `deleting` hook) delete the subject's assignments; `DeleteFlagAction` ignores assignments whose subject is gone.
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
  1. Validate each record against `CreateProductData::rules()` or `UpdateProductData::rules()`, chosen by whether it matched. Invalid records go to `failures`. For records whose `flags` change, also check `assign` here, before the preview and the bulk path; a refusal is a guard-refused failure at `flags`.
  2. Load the matched products in one query, by identifier or through `ExternalIds::find()`. Eager-load values, family, parent, owner, categories, associations, external ids and own flags.
  3. Preview: normalize each record's sent fields and the stored product into the same shape and compare them in memory. Equal records count as `unchanged`.
  4. Write the changed and new records inside `ProductIndex::deferring()`, so one index sync is queued for the batch.
     - New records whose fields are all simple (no associations, no assets) are bulk-inserted: products, slugs (`Slugs::forMany`), unique values, external ids, flag assignments and one `created` version per product, in one transaction per chunk.
     - A write failure inside a bulk chunk falls back to the per-record path for that chunk, so one bad record never rolls back its neighbours.
     - Every other record goes through `CreateProductAction` or `UpdateProductAction` in its own transaction, so domain guards and per-record failure isolation stay exact.
  5. With `captureChanges: false`, the writes run inside `ProductChangeCapture::suppress(fn () => …)`. That is a core, scoped service whose `suppress()` restores the previous state in a `finally`; `CaptureProductChanges` in `src/Impex` checks it before recording.
- **Impex integration:**
  - `ImportProductRow` is replaced by `Impex/Actions/UpsertProductChunk`, which calls `UpsertProductsAction` once per chunk.
  - `InlineProductSource` and `FileProductSource` yield chunks.
  - `UpsertProductsFlow` and `ImportProductsFlow` accept `capture_changes` and pass it through.
  - Failures are stored per row on the batch item.
  - **Upgrade note (breaking):** `ImportProductRow` and its per-row `ProductRowImportedActionEvent` are replaced by per-chunk processing, and run results gain `unchanged`. Consumers listening for the per-row event stop receiving it. List this in the changelog.
- **Authorization:** the HTTP and MCP start actions already authorize. The action itself runs as the caller, like the existing per-row import. New records with flags, and updated records whose flags change, need `assign` (AC 37). Updated records also need update permission as today.

### Atrium

- **Registration:** `Atrium/ShowroomPlugin.php` registers the new screens and fields through the same Atrium screen registry it uses for the product and owner screens. A new `Atrium/FlagScreen` (resource screen over the flag actions) provides the flag-definitions screen.
- **Flag definitions:** list, create, edit and delete, with `code` read-only on edit and the delete refusal message from the action shown to the user.
- **Product and owner screens:** a read-only `slug`, an `external_ids` key/value map editor, an own-flags multi-select limited to flags whose `applies_to` includes the subject, and read-only `effective_flags` with inherited flags marked. The own-flags field is hidden when the user lacks `assign`.
- **Links:** use the configured lookup key.

### Config

- `products.identifier_pattern`
- `lookup.products` and `lookup.owners` (each `id` or `slug`; default `id`)
- `impex.chunk`: kept, and used for upsert chunks
- `tables.external_ids`, `tables.flags`, `tables.flag_assignments`
- `policies.flag`

### Upgrade notes

- Product and owner URLs built from the identifier or code (bookmarks, stored Atrium links, API clients) break after upgrade, because routes bind by `id` by default. There is no identifier or code fallback (D-010). Clients switch to the `id` (or `slug`, when the host configures it), both of which every resource returns.
- Clients that echo a full resource back on update must strip read-only keys (`slug`, `effective_flags`).
- The `ImportProductRow` and `ProductRowImportedActionEvent` change (see Impex integration).

## Out of scope

- Owner values and metadata: logos, descriptions, owner attributes.
- Keeping ERP-owned changes from demoting `approved` products to `draft`. That's needed before a D365 sync.
- Configurable workflow statuses.
- External ids and flags on product models.
- HTTP and MCP endpoints for the batch upsert itself.
- Flag negation (a child clearing an inherited flag).
- Any host-app flow, such as `core:catalog`.
- Slug redirects or aliases for stale URLs, and regenerating or editing slugs.
- `external_ids` and `flags` in export and import file formats (exports carry `slug` only).
- Webhook payload changes for the new fields (slug is not added to payloads in this spec).

## Risks and open questions

- **Bulk insert vs actions:** the bulk-insert path for new simple products duplicates part of `CreateProductAction`. The risk is drift from the per-record behaviour. Mitigation: a shared write step, and tests that compare a product created through the bulk path with one created through the action.
- **Scout mappings:** covered by AC 34. No consumer runs Showroom on Scout yet, so there is no live index to migrate or reindex on upgrade.
- **Backfill time:** generating slugs for millions of existing rows is slow. Showroom installs today are small, but the migration works in chunks and is resumable (AC 6).
- **Effective flags cost:** a deep owner chain makes effective-flag resolution walk the path. Owner counts are small, about 3k, so it's resolved per batch and not cached. Revisit if owners grow.
- **Slug truncation:** a 191-character identifier plus a suffix needs truncation before the suffix. Covered by AC 3.

## Change log: decisions D-001 to D-009

All decisions are folded into the body above and the numbered ACs are authoritative.

- D-001 record lookup: AC 8 to 11, Design > Record lookup, Config, Upgrade notes.
- D-010 lookup keys limited to `id` and `slug`: AC 1, 9, 10, Design > Record lookup, Config, Upgrade notes.
- D-002, D-003 scope: Goal, AC 40 and 41, Design > Atrium.
- D-004 flag permission: AC 19 and 37, Design > Flags and Batch upsert.
- D-005 case: AC 36.
- D-006 empty or reserved slugs: AC 4 and 7, Design > Slugs.
- D-007 query bound: AC 27 and 29.
- D-008 parity: AC 38 and 39.
- D-009 webhook opt-out tests: AC 30 and 35.

## Addendum (2026-11-10): build decisions D-011 and D-012

- **AC 31 (D-011):** turning off capture on the CLI uses `--capture-changes=0`, not `--no-capture-changes`. The flows declare `?bool $captureChanges = null`, where `null` means `true`. A test runs each flow with `--capture-changes=0` and asserts that nothing is recorded for webhooks.
- **AC 32 (D-012):** the arch rule "code outside `src/Impex` uses no Impex class" names two exceptions: `RefactorCircus\Showroom\Domains\Transfer` and `RefactorCircus\Showroom\Atrium\Http\Controllers\TransferUiController`. Both are the bridge that started Impex runs before this spec. Code this spec adds outside `src/Impex`, including `ProductChangeCapture`, must not use Impex.
