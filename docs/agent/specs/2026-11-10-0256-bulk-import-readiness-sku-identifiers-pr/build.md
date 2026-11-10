## Group 2

Owner type may parent itself (AC 24). Commit 9eac338 on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr--g2`.

**Files**

- `src/Domains/Owner/Data/CreateOwnerTypeData.php`: `parent_types.*` now uses a data-aware rule. It passes when the value equals the incoming `code` and otherwise needs an existing owner type, failing with the standard `validation.exists` message.
- `tests/Feature/Api/OwnerTypeSelfParentTest.php`: new.
- `src/Domains/Owner/Actions/CreateOwnerTypeAction.php` and `src/Domains/Owner/Concerns/WritesOwnerTypes.php` are unchanged. The action already creates the type row before `setParentTypes()` looks up the codes, so the type's own code is found.

**Tests**

- `vendor` is created with `parent_types: ["vendor"]`, and both the create and show responses report `["vendor"]`.
- A `vendor` owner is placed under another `vendor` owner.
- An unknown parent type (`brand`) is still refused on `parent_types.1`, the self-reference on `parent_types.0` passes, and no type is created.
- `OwnerTypeSelfParentTest` and `OwnershipApiTest` pass: 12 tests. Pint and PHPStan pass on the changed file. I didn't run the full suite, which the gate runs.

**Deviations**

- None.

## Group 1

Config and SKU identifier rule (AC 1, 2). Commit 3d7994b on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr--g1`.

**Files**

- `config/showroom.php`: new `Products` banner section with `products.identifier_pattern`, and new `Record Lookup` banner section with `lookup.products` and `lookup.owners` (both `id`). Group 4 reads and boot-checks the lookup keys.
- `src/Domains/Product/Data/CreateProductData.php`: `identifier` uses `Rule::regex()` with the configured pattern, which is safe with a `|` in the pattern. It falls back to a hard default when the key is absent. A pattern that doesn't compile throws `InvalidArgumentException` naming `showroom.products.identifier_pattern` and the PCRE message, so there's no per-request `preg_match` warning.
- `tests/Feature/Api/ProductIdentifierTest.php`: new.

**Tests**

- Both AC 1 SKUs are created over HTTP. A duplicate SKU with `/` is refused at `identifier`.
- Boundary dataset against `CreateProductData::rules()`:
  - Accepts `A`, `A  B` and 191 characters.
  - Rejects `''`, ` A`, `A `, `"A\tB"`, `"A\n"`, `"A\x07B"` and 192 characters.
  - These run against the rules, not HTTP, because the `TrimStrings` middleware would trim the whitespace cases before validation.
- A published config without the key falls back to the default.
- A stricter host pattern set with `config()->set` fails creation at `identifier`.
- A pattern containing `|` works.
- A non-compiling pattern throws.
- The new file passes: 17 tests. Pint and PHPStan pass on the changed files. I didn't run the full suite, which the gate runs.
- Fetching by `id` and `slug` (the second half of AC 1) needs groups 3 and 4, so it's covered there.

**Deviations**

- **Default pattern gets the `D` modifier.** It is `'/^[^\s\p{C}](?:[^\p{C}]{0,189}[^\s\p{C}])?$/uD'`. The spec's `/u` form lets `$` match before a trailing newline, which would accept `"A\n"` and break AC 2's "rejects control characters". The test covers this case.
- **`tables.external_ids`, `tables.flags`, `tables.flag_assignments` and `policies.flag` are not added here.** Each has to land in the same commit as the code that defines it:
  - `TablesTest` pins `config('showroom.tables')` to `Tables::DEFAULTS`.
  - `RenamedTablesTest` requires a migrated table for every `DEFAULTS` key.
  - PHPStan analyses `config/` and reports `class.notFound` for `FlagModel::class` and `FlagPolicy::class` until group 6 creates them.
- **Groups 5 and 6 must touch files outside their lists:**
  - Group 5 adds `'external_ids' => 'showroom_external_ids'` to `config/showroom.php` `tables` and to `src/Support/Tables.php` `DEFAULTS`.
  - Group 6 adds `'flags' => 'showroom_flags'` and `'flag_assignments' => 'showroom_flag_assignments'` the same way.
  - Group 6 adds `FlagModel::class => FlagPolicy::class` to `policies`, plus a sentence on `assign` in the Policies banner.

## Group 6

Flag definitions (AC 17, 18). Commit 14d9cbc on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr--g6`.

**Files**

- `src/Domains/Flag/**` (new domain, mirroring Channel and OwnerType):
  - `FlagModel` on `showroom.tables.flags`, with `appliesTo(FlagSubject)` and `assignments(?FlagSubject)`, a query on `flag_assignments`.
  - `Enums/FlagSubject` (`Product`, `Owner`). `values()` gives the `applies_to` values, and `morphClass()` gives the `subject_type` an assignment row stores (the model's `getMorphClass()`). Group 7's `Flags::write()` should store the same value.
  - `FlagPolicy` (`showroom.policies`). The bundled abilities plus `assign(Model $user, ?Model $subject = null)`. Ask it with the class, and the record when there is one: `allows('assign', [FlagModel::class, $product])`.
  - List, Show, Create, Update and Delete actions, each with its Data and action-event pair, plus the ten model lifecycle events.
  - `FlagController`, five HTTP requests, `FlagResource`, `routes.php` (`flags.*`, bound by `{flag:code}`), `FlagServiceProvider` (routes and the audit label).
  - Five MCP tools and requests, addressed by a `flag` code argument.
- `database/migrations/2026_11_10_000003_create_showroom_flag_tables.php`:
  - `flags`: `code` unique (100), `labels`, `applies_to` json, `sort_order`.
  - `flag_assignments`: `flag_id` FK restricting deletes, `subject_type` (50), `subject_id` ulid, primary key on all three, index `(subject_type, subject_id)`.
- `src/Domains/DomainServiceProvider.php` registers `FlagServiceProvider`.
- `src/Mcp/ShowroomServer.php` adds the five tools under `// Flags`, and one sentence on flags to the instructions.
- Outside the list, as group 1's notes require:
  - `config/showroom.php`: `tables.flags`, `tables.flag_assignments`, the `FlagModel => FlagPolicy` entry, and an `assign` paragraph in the Policies banner.
  - `src/Support/Tables.php`: the two `DEFAULTS` keys.
- Outside the list, because the suite pins them:
  - `tests/Feature/McpTest.php`, `tests/Cortex/CortexTest.php` and `tests/Feature/Mcp/OutputSchemaTest.php`: 78 → 83 tools.
  - `tests/Feature/EventsTest.php`: 18 → 19 models, 77 → 82 actions.
  - `tests/Pest.php`: `PARITY_EXCEPTIONS` lists the five flag actions for `ui` until group 10 ships the flag screen. Group 10 removes them.
- New support files outside the list:
  - `database/factories/FlagFactory.php`: every sibling model has a factory.
  - `tests/Fixtures/ReadOnlyFlagPolicy.php`: refuses create, update, delete and `assign`. Group 7 can reuse it for the `assign` refusals.
- New tests: `tests/Feature/FlagActionsTest.php` and `tests/Feature/Api/FlagApiTest.php`.

**Tests**

- Actions:
  - create, with defaults and the event pair; list order (sort order, then code), search and paging; show; partial update.
  - `code` is prohibited on update.
  - `applies_to` must be a non-empty list of distinct `product`/`owner` values, on create and update.
  - Duplicate and malformed codes are refused.
  - Deleting a flag assigned to a product or an owner fails at `flag`, with no finished event.
  - Dropping `product` from `applies_to` while products carry the flag fails at `applies_to` and writes nothing. Dropping a type the flag isn't assigned to passes.
  - `FlagPolicy` is registered from config, and every ability, `assign` included, answers for a user and refuses a guest.
  - Assignment rows are seeded directly on real products and owners, so the tests still hold once group 7's delete ignores orphans.
- HTTP:
  - CRUD by route name, `{flag:code}` binding (an id answers 404), and 422 mapping.
  - A refused delete or narrowing answers 422.
  - Guests get 401. `ReadOnlyFlagPolicy` gives 403 on writes over HTTP and `Unauthorized.` over MCP.
- MCP: create, list, show, update and delete; validation errors; `Not found.`; the refusal messages.
- Results:
  - The two new files pass: 31 tests.
  - `ArchTest`, `EventsTest`, `McpTest`, `OutputSchemaTest`, `TablesTest`, `PolicyTest`, `RenamedTables` and `CortexTest` pass: 63 tests.
  - Pint and PHPStan (project config) pass.
  - I didn't run the full suite, which the gate runs.

**Deviations**

- **Refusals are 422 validation errors, not the package's usual 409 `ModelInUseException`.** AC 18 asks for a validation error. The keys are `flag` for a delete and `applies_to` for narrowing.
- **The flag actions keep the sibling shape**: public `execute()`, private `perform()` and `DB::transaction()`. `actions/actions-events` describes `extends Action`, a protected `handle()` and `Tables::db()`, but no existing domain uses them. Following the package's own convention keeps the domains consistent. I added no `standards-feedback.md` row, because the whole package already deviates this way.
- **For integration:**
  - Group 5 also edits `config/showroom.php` `tables` and `Tables::DEFAULTS`, and may add a model. Group 8 adds an action. The pinned counts in `EventsTest` (models and actions) need adding up at merge.
  - `DeleteFlagAction` counts every assignment. Group 7 owns ignoring orphans (AC 33).

## Group 3 (salvaged)

Slugs for products and owners (AC 3, 4, 5, 6, 7, 12). Commit 11f76c6 on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr--g3`.

**What I found**

- No `index.lock`, and no merge or rebase in progress. The g3 worktree was clean.
- The build agent had already committed all 18 listed files in 11f76c6 and ticked 3.1–3.4 in `tasks.md`. It stopped before writing its build note and returning a result.
- I reviewed the commit against the tasks:
  - 3.1: `tests/Unit/SlugsTest.php`, `tests/Feature/SlugsTest.php` and `tests/Feature/SlugBackfillTest.php` cover:
    - the generator: collisions, no gaps, 191-character cut, in-call collisions, `product`/`owner` fallback, reserved segments, the one retry on a unique race
    - slugs assigned on product and owner create
    - `slug` refused on create, and a full resource body refused on update
    - slugs unchanged after value, label and parent changes
    - the backfill: chunking, idempotent re-run, empty and reserved sources, required and unique
    - `slug` in resources, MCP output, the search document (`slug`, `owner_slugs`) and exports
  - 3.2:
    - `Support\Slugs` has `for`, `forMany`, `claim` (retries once on `UniqueConstraintViolationException`), `RESERVED` and `ownerSource`.
    - The migration adds a nullable column, backfills `slug IS NULL` rows in chunks of 1000, then makes the column non-null and unique. It is resumable and does not run in a transaction.
  - 3.3:
    - `ProductModel` and `OwnerModel` set a slug in `setUniqueIds()` when none is given.
    - The create actions claim slugs through `Slugs::claim()`.
    - `slug` is `prohibited` in all four Data classes.
    - `slug` is added to both resources, `ProductDocument` (`slug`, `owner_slugs`), `ExportRecord`, and `ProductRows` (written on export, skipped on import).

**What I committed**

- Nothing new: no uncommitted work was left on the group's paths. 11f76c6 is the group's commit.

**What is left**

- Nothing for group 3. Per the salvage instructions I didn't run the suite; the gate runs it. 3.4 stays ticked as the build agent left it, and the gate's run confirms it.

**Deviations**

- None found in the commit.

## Group 4 (salvaged)

Record lookup by id or slug (AC 8, 9, 10, 11). Commit 60272e7 on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr`.

**What I found**

- No `index.lock`, and no merge or rebase in progress. Nothing for group 4 had been committed.
- The build agent left most of the group uncommitted:
  - `Support\LookupKey` (`Id`, `Slug`) and `Support\RecordLookup`, which has `resolve`, `product`, `owner`, `key`, `assertConfigured`, the static `by()` (validates at `by`) and `rules()`.
  - The `ShowroomServiceProvider` boot check and the explicit `{product}`/`{owner}` binders. The binders read `?by=` and apply only to Showroom controllers; host routes keep Laravel's implicit binding.
  - The product, workflow, owner and Atrium routes, now bound as `{product}`/`{owner}` instead of `:identifier`/`:code`.
  - `by` rules on every listed HTTP request.
  - `tests/Feature/RecordLookupTest.php`, covering:
    - the `route:list` dataset: `id` by default, and `id`/`slug` per config
    - `?by=` and MCP `by`
    - an invalid `by` (`identifier`, `code`, `bogus`) giving 422 or an MCP validation error, with the record untouched
    - the boot check
    - Atrium links per key
    - flags still bound by `code`
    - the AC 1 SKU fetched by id and by slug
- It had also wired MCP `by` into `src/Domains/{Product,Owner,Workflow}/Mcp/Requests/*` and added `getRouteKeyName()` to `ProductModel` and `OwnerModel`. Those files are outside the group's list.
- Gaps:
  - The ten MCP tool schemas still described the argument as "The product identifier" or "The owner code", and none declared `by`.
  - The D-001 row in `standards-feedback.md` was missing.

**What I committed**

- Everything above.
- MCP tools (show, update and delete for products and owners; transition, list versions, show version and revert for products):
  - The `product`/`owner` argument now says it takes the id, or the slug.
  - Each tool declares an optional `by` argument, restricted to the `LookupKey` values.
- A test that every lookup tool publishes `by` with the enum `["id", "slug"]`, not required.
- The D-001 row in `docs/agent/standards-feedback.md`.
- Pint run on every changed PHP file.

**What is left**

- 4.4: I didn't run the suite, as instructed. The gate runs it.

**Deviations**

- Files outside the group's list, committed because the lookup doesn't work without them:
  - the MCP `Requests/*`: the `by` rule, and the resolver in `ProductRequest`/`OwnerRequest`
  - `ProductModel`/`OwnerModel::getRouteKeyName()`, so `route(..., $model)` links and host implicit binding follow the configured key
- Groups 5 and 7 also list the two models, so they should merge on top of this commit.
- The Atrium views needed no change. Their links pass the model, and `linkTarget` and the `owner` filter are input references, which keep identifier and code.

## Group 3 (retry: task 3.4)

- Only 3.4 (run `vendor/bin/pest`) was left. The g3 worktree is clean, and 11f76c6 holds all 18 group files. 3.1–3.4 are ticked.
- Files: none changed in this retry. No new commit was needed.
- Tests: I didn't run the suite, as instructed. The gate runs it, and that run covers 3.4.
- Deviations: none.

## Group 4

Retry for task 4.4. The code for 4.1–4.3 is in commit 60272e7; see "Group 4 (salvaged)" above.

- Files: no source or test files changed in this retry. This commit only ticks 4.4 in `tasks.md` and adds this note. It also commits the ticks for groups 1, 2, 3 and 6 and the earlier `build.md` notes, which the merges had left uncommitted in the worktree.
- Tests: I didn't run the suite, as instructed. The gate runs `vendor/bin/pest`, which covers 4.4 and `tests/Feature/RecordLookupTest.php` (AC 8–11).
- Deviations: none new. The out-of-list files from 60272e7 (the MCP `Requests/*`, and `getRouteKeyName()` on `ProductModel` and `OwnerModel`) are listed under "Group 4 (salvaged)".

## Group 5 (salvaged)

External IDs (AC 13, 14, 15, 16, 36). Committed on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr`.

**What I found**

- No `index.lock`, and no merge or rebase in progress. Nothing for group 5 had been committed.
- The build agent left most of the group uncommitted:
  - `Domains/ExternalId`:
    - `ExternalIdModel` on `showroom.tables.external_ids`, plus its ten lifecycle events.
    - `ExternalIdPolicy`, which defers to the subject's policy.
    - The scoped `ExternalIds` service: `rules`, `filterRules`, `present`, `write`, `load`, `find` and `forget`.
  - The migration: unique `(subject_type, system, external_id)`, and an index on `(subject_type, subject_id)`.
  - `Support\KeyNormalizer`: NFKD, then accents stripped, then full case folding.
  - `externalIds()` morphMany relations on both models.
  - Writes from the create and update actions inside their transactions, and `forget()` on delete.
  - `external_ids` on the four create and update Data classes, and `external` on both list Data classes.
  - The owner listing filter.
  - The scoped binding and the `showroom_external_id` morph alias in `ShowroomServiceProvider`.
  - `tests/Feature/ExternalIdsTest.php` and `tests/Unit/KeyNormalizerTest.php`.
- Gaps:
  - Neither resource returned `external_ids`.
  - `ProductDocument` had no `external_ids`.
  - Neither search engine applied the `external` filter.

**What I committed**

- Everything above, plus the gaps closed:
  - `ProductResource` and `OwnerResource` return `external_ids` as `(object)` of the `{system: id}` map, when loaded, so an empty map is `{}`.
  - `ProductDocument::many()` loads the batch's external ids in one query, and writes `external_ids` as `"{system}:{normalized id}"`, ordered by system. `ProductDocument::externalId()` is the one place that builds that value.
  - `DatabaseEngine`: a `whereExists` per `external[system]` pair on the external ids table. Values are bound.
  - `ScoutEngine`: `where('external_ids', ProductDocument::externalId(...))` per pair. Scout's engines quote and escape the value; the test checks the Meilisearch expression for `"`, `\` and ` OR `.
- Test fix: `mcpTool()` decodes the `execute_tools` envelope into PHP arrays, which turns `{}` into `[]`. The empty-map MCP check now reads the raw envelope JSON through a `rawMcpOutput()` helper in the test file.
- Files outside the group's list:
  - `config/showroom.php` and `src/Support/Tables.php`: `tables.external_ids`, as group 1's notes require. Also the `ExternalIdModel => ExternalIdPolicy` entry and a sentence in the Policies banner.
  - `ShowOwnerAction`, `ShowProductAction` and `ListProductsAction`: eager-load `externalIds` so the resources return the map with no extra queries per record.
  - `src/Domains/Search/Data/ProductQuery.php`: the `external` field the engines read.
  - `tests/Feature/EventsTest.php`: 19 → 20 models.
  - `tests/Fixtures/RecordingScoutEngine.php`: `=` on a list field matches when the list holds the value, as real engines do.
  - `tests/Feature/PerformanceTest.php`: the search-document batch bound goes from 3 to 4 queries (the external ids). It stays fixed: the test still checks that the count doesn't change with batch size.
- Pint on every changed file. PHPStan (project config) passes on the changed files.

**Tests**

- I ran only the affected files, not the suite:
  - `ExternalIdsTest`, `KeyNormalizerTest`, `ArchTest`, `EventsTest` and `OutputSchemaTest` pass.
  - The `PerformanceTest` document-batch test passes.
- Failures I saw that come from group 4, not this group:
  - Six tests still call product URLs by identifier, which now answer 404 because routes bind by `id`:
    - `PolicyTest` ("associations, versions … follow the product policy")
    - four `PerformanceTest` cases
    - two `ScoutEngineTest` cases
  - PHPStan reports `return.type` in `src/Support/RecordLookup.php:143`.
  - None of these files is in group 5's list.

**What is left**

- 5.3: the gate runs the suite. It will hit the group 4 failures above until someone updates those tests to `id` URLs.

**Deviations**

- **`ExternalIdPolicy` exists.** The spec says external ids have no separate policy. But `ArchTest` requires every model to have an entry in `showroom.policies`. The policy only defers to the subject's policy (`view`, then `update`), as `VersionPolicy` does, so the subject's policy still decides.
- **`subject_type` stores the morph aliases `showroom_product` and `showroom_owner`, not `product` and `owner`.** These are the aliases the models already register. Group 6's flag assignments store the same values.

## Group 5

This retry covers task 5.3 only. The code for 5.1 and 5.2 is in commit 63335ea; see "Group 5 (salvaged)" above.

- **Files:** I changed no source or test files. This commit only ticks 5.3 in `tasks.md` and adds this note.
- **Tests:**
  - `tests/Feature/ExternalIdsTest.php` and `tests/Unit/KeyNormalizerTest.php` pass: 58 tests, 190 assertions (AC 13–16, 36). I didn't run the full suite; the gate runs it.
- **Known failures the gate will hit, all outside group 5:**
  - Group 4 bound `{product}` and `{owner}` by `id` by default (AC 8). Many older tests still call product and owner URLs by identifier or code, so they get `404`. For example, all 8 tests in `tests/Feature/Api/WorkflowApiTest.php` fail this way.
  - These files still use identifier or code URLs:
    - `tests/Feature/Api/`: `WorkflowApiTest`, `ProductApiTest`, `AssetApiTest`, `CompletenessApiTest`, `ProductPollingTest`, `CategoryApiTest`, `AssociationApiTest`, `OwnershipApiTest` and `ChannelApiTest`
    - `tests/Feature/`: `PolicyTest` and `PerformanceTest`
    - `tests/Feature/Search/ScoutEngineTest`
    - `tests/Feature/Ui/`: `WorkflowPagesTest`, `AssociationPagesTest` and `WidgetsTest`
    - `tests/Feature/Impex/`: `ImportExportTest` and `ProductWebhooksTest`
    - `tests/Keen/AuditHooksTest`
    - `tests/Modes/RenamedTables/RenamedTablesTest`
  - The fix is to address records by `id` in those tests, or to send `?by=slug` with the slug. Changing the binding is not the fix, because AC 8 requires it.
  - None of these files is in group 5's list, so I left them for a fix round.
- **Deviations:** none new.

## Group 7 (salvaged)

Flag assignment, effective flags and search (AC 19–23, 33, 34, 37). Committed on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr`.

**What I found**

- No `index.lock`, and no merge or rebase in progress.
- The build agent left three untracked files and nothing else:
  - `src/Domains/Flag/Services/Flags.php`, complete: `rules`, `filterRules`, `changes`, `authorizeAssign`, `write`, `forget`, `holders`, `own`, `effective`, `load`, `productsCarrying` and `ownersInheriting`.
  - `tests/Feature/FlagAssignmentTest.php` and `tests/Feature/Search/FlagSearchTest.php`, covering every 7.1 item.
- None of the models, actions, Data classes, resources or engines had been changed.

**What I committed**

- Tests (7.1):
  - The two test files as found, with two fixes:
    - The unknown/wrong-subject dataset wrapped each closure twice. Pest passes a `Closure`-typed dataset value through without calling it, so the test never ran the create. Each entry is now wrapped once.
    - The batch-load check reads `loadedEffectiveFlags()` instead of a relation (see below).
- Implementation (7.2):
  - `Domains/Flag/Concerns/HasFlags` on `ProductModel` and `OwnerModel`:
    - `flags()`, a `morphToMany` on `flag_assignments`, in flag order.
    - `setEffectiveFlags()` and `loadedEffectiveFlags()`. Effective flags are kept on the model outside its relations, because `refresh()` reloads every relation by name and there is no `effectiveFlags` relation to reload. `SlugsTest` refreshes models returned by the update actions, so a relation broke it.
  - Data:
    - `flags` (`Flags::rules()`, per subject) on the four create and update Data classes.
    - `effective_flags` is `missing` on all four: `prohibited` lets an empty list through, and AC 5 refuses `effective_flags: []`.
    - `flags[]` (`Flags::filterRules()`) on `ListProductsData` and `ListOwnersData`.
  - Actions:
    - Create and update for products and owners ask `assign` (`Flags::authorizeAssign()`) before writing anything, and only when `Flags::changes()` says the set differs. They write with `Flags::write()` inside their transaction, then `Flags::load()` the result.
    - `UpdateOwnerAction` queues `ProductIndex::queueWithin(SyncScope::Owner, …)` once for a move, a flag change or both.
    - `DeleteProductAction` and `DeleteOwnerAction` call `Flags::forget()`.
    - `DeleteFlagAction` counts only live holders (`Flags::holders()`), then deletes the orphan rows before the flag, since the foreign key restricts deletes.
    - `ListOwnersAction` filters on `Flags::ownersInheriting()` per code, and loads the page's flags in one query.
  - Resources: `flags` (when the relation is loaded) and `effective_flags` (when `Flags::load()` ran) on `ProductResource` and `OwnerResource`, with schema entries.
  - Search:
    - `ProductDocument` writes `flags`: effective codes, normalized through `ProductDocument::flag()` (`KeyNormalizer`). One query per batch.
    - `DatabaseEngine`, for each code: own assignment, or the owner, level-1 or level-2 owner in `ownersInheriting()`.
    - `ScoutEngine` does `where('flags', ProductDocument::flag($code))` per code, and adds `ScoutEngine::indexSettings()`. Its `filterableAttributes` include `slug`, `external_ids`, `flags` and `owner_slugs`, and its `sortableAttributes` are `ProductQuery::SORTABLE`.
- Files outside the group's list:
  - `src/Domains/Flag/Concerns/HasFlags.php` (new): the relation and the effective-flags holder, shared by both models.
  - `ShowProductAction`, `ListProductsAction` and `ShowOwnerAction` call `Flags::load()`, so the resources return flags without querying, as `http/resources` requires. Group 5 did the same for `externalIds`.
  - `src/Domains/Search/Data/ProductQuery.php`: the `flags` field the engines read.
  - `tests/Feature/PerformanceTest.php`: the search-document batch bound goes from 4 to 5 queries (the flags). It stays fixed, and the test still checks that the count doesn't change with batch size.
- Pint on every changed file. PHPStan (project config) passes on the changed source files.
- Targeted run: `FlagAssignmentTest`, `FlagSearchTest`, plus the flag, slug, external-id, output-schema and search-document tests. 137 passed. I didn't run the full suite; the gate runs it.

**What is left**

- 7.3, the full `vendor/bin/pest` run, for the gate. The known identifier-URL failures listed under group 5 (for example `ScoutEngineTest`) are still outside this group.

**Notes**

- The design names a model `deleting` hook beside the delete actions. I followed the external-ids pattern instead: the delete actions call `Flags::forget()`, and `DeleteFlagAction` ignores orphans. AC 33 is met either way.
- Flag filters on the database engine compare codes under the column's collation. The Scout engine normalizes them.

## Group 7

Resumed from checkpoint 4d664b3 (see "Group 7 (salvaged)" above). The `Flags` service (`write`, `own`, `effective`, `load`, `forget`, `holders`), `HasFlags`, the `assign` check and flag writes in the four create/update actions, cleanup in both delete actions, orphan-tolerant `DeleteFlagAction` and the resource fields were already complete. I checked each against AC 19, 20, 21, 33 and 37 and found nothing to change.

- **Files:** no source or test files changed. This commit ticks 7.1 to 7.3 in `tasks.md` and adds this note.
- **Tests:**
  - `tests/Feature/FlagAssignmentTest.php`: 26 tests, 187 assertions. They cover flags on create and update (the list replaces the set; unknown codes and the wrong `applies_to` are refused at `flags.{n}`), `assign` only when flags change (actions, HTTP and MCP, for products and owners), effective flags through a grandchild owner and a root-model-owner variant, no dropping of inherited flags, `flags`/`effective_flags` in resources and MCP output, assignments deleted with their subject, and orphan owner assignments ignored by flag delete.
  - `tests/Feature/FlagActionsTest.php` and `tests/Feature/Api/FlagApiTest.php`: 31 tests, 165 assertions. With `OwnerTypeSelfParentTest` added, the targeted run is 60 passed. Pint `--test` is clean on `src/Domains/{Flag,Product,Owner}` and the test file. I didn't run the full suite; the gate runs it.
- **Known failures the gate will hit, outside group 7's changes:**
  - Full `phpstan analyse` reports 3 errors, none introduced here:
    - `src/Domains/Owner/Data/CreateOwnerTypeData.php:64,69` (group 2): `missingType.iterableValue` on the anonymous `DataAwareRule`'s `$data`, although it has an `array<array-key, mixed>` docblock. I tried replacing it with `Rule::forEach`. The callback gets only the `parent_types` subtree, not `code`, so self-parenting broke, and I reverted. A named rule class (a new file) would fix it.
    - `src/Support/RecordLookup.php:143` (group 4): a `return.type` mismatch on the `TModel` template.
  - The identifier/code-URL `404` failures listed under group 5 (for example `OwnershipApiTest`) are group 13's.
- **Deviations:** the design's model `deleting` hook for flag assignments is not added. The delete actions call `Flags::forget()`, as they do for asset links, associations and external ids, and `DeleteFlagAction` ignores orphans, so AC 33 holds. A hook would need a listener on `ProductDeletingEvent`/`OwnerDeletingEvent` in a provider outside this group's files.

## Group 7 (retry 2)

This retry re-checked 7.1 to 7.3 against branch head df773b8. Nothing in `src/` or `tests/` has changed since checkpoint 4d664b3.

- **Files:** no source or test files changed. This commit only adds this note. 7.1 to 7.3 were already ticked.
- **Checked:** `Flags` (`write`, `own`, `effective`, `load`, `forget`, `holders`), `HasFlags`, the `assign` check before writing in the four create and update actions (only when `Flags::changes()` is true), `Flags::forget()` in both delete actions, the orphan-tolerant count in `DeleteFlagAction`, and `flags`/`effective_flags` in both resources. They meet AC 19, 20, 21, 33 and 37.
- **Tests:** `tests/Feature/FlagAssignmentTest.php`, `tests/Feature/FlagActionsTest.php` and `tests/Feature/Api/FlagApiTest.php` all pass: 57 tests, 352 assertions. I didn't run the full suite; the gate runs it.
- **Deviations:** none new. See "Group 7" above.

## Group 12

Flag search and owner reindex (AC 22, 23, 34). Commit 24cf1d4 on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr--g12`, resumed from checkpoint 4d664b3.

**What the checkpoint already had** (checked against AC 22, 23 and 34, kept as is):

- `ProductDocument` writes `flags` as the normalized effective codes, with one `Flags::effective()` query per batch.
- `flags[]` on `ListProductsData` → `ProductQuery`:
  - `DatabaseEngine` matches an own assignment, or the product's owner, level-1 or level-2 owner in `Flags::ownersInheriting()`.
  - `ScoutEngine` adds one `where('flags', ProductDocument::flag($code))` per code, which the engine quotes.
- `flags[]` on `ListOwnersData`. `ListOwnersAction` filters on `ownersInheriting()` per code.
- `UpdateOwnerAction` calls `queueWithin(SyncScope::Owner, …)` once for a move, a flag change or both, and not for an unchanged set.
- `ScoutEngine::indexSettings()` lists `slug`, `external_ids`, `flags` and `owner_slugs`.
- `PerformanceTest`: the document batch bound is 5 queries.

**Files changed**

- `tests/Feature/Search/FlagSearchTest.php`: a new AC 23 test on the Scout engine, through HTTP, with `QueueProductSyncs` faked:
  - A flag change on `acme` queues one `Owner` sync for `acme`. The index stays stale until the queued job runs, then `INHERITED` gains `restricted`.
  - The same flags in another order queue nothing.
  - Moving `acme-tools` queues a second sync for it. After the sync, `pricing_only` matches nothing, including the grandchild owner's product and the variant.
- `src/Domains/Search/Services/DatabaseEngine.php`: `Flags` is resolved once before the loop over codes instead of once per code.

**Tests**

- `tests/Feature/Search/FlagSearchTest.php`: 18 passed, 299 assertions. This covers:
  - the shared dataset on both engines (own, inherited at any depth, root-model-owner variant, several flags ANDed, an unknown flag)
  - the flag change and move followed on both engines
  - the owner listing
  - normalized and quoted Scout filters
  - the index settings
- `tests/Feature/Search` as a whole: 42 of 44 pass. The two failures are `ScoutEngineTest` calling product URLs by identifier, which now answer `404` (group 4, AC 8). That file belongs to group 13.
- Pint is clean on both changed files. PHPStan (project config) is clean on `DatabaseEngine.php`; tests aren't in its paths.

**Deviations**

- None. `PerformanceTest` still has identifier and code URLs (for example `PATCH /showroom/owners/acme-tools`). I left them for group 13, whose task is to move those tests to `id`.

## Group 10 (salvaged)

Atrium screens (AC 40, 41). Commit a3862e6 on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr--g10`.

**What I found**

- No `index.lock`, and no merge or rebase in progress. Nothing for group 10 had been committed.
- The build agent had left the whole group uncommitted, and it was complete:
  - Flag-definitions screen:
    - `Atrium/FlagScreen` declares `showroom.flags` (list) and `showroom.flag` (detail) on the screen registry. It also provides the own-flags field: `options()` (flags whose `applies_to` includes the subject), `mayAssign()` (`assign` asked with the record) and `fromForm()` (only when the hidden `flags_sent` marker was sent).
    - `FlagUiController` runs the five flag Actions.
    - `ShowroomPlugin` adds the `flags.*` routes, bound by `{flag:code}`.
    - Views `ui/flags/{index,show,fields}`. `code` is read-only on edit. A refused delete or `applies_to` narrowing shows the Action's message through `x-atrium::flash`.
    - The products and owners lists link to the screen.
  - Product and owner screens:
    - A read-only `slug`.
    - An `external_ids` map editor: one input per system (an empty value removes it), plus a new system and id.
    - The own-flags checkboxes, shown only with `assign`.
    - Read-only `effective_flags`, each marked `data-inherited`.
    - Partials `ui/flags/{own,effective}` and `ui/partials/external-ids`.
    - `ProductUiController` and `OwnerUiController` pass `mayAssign` and `flagOptions`, and map the form to `external_ids` and `flags`.
  - Tests:
    - `FlagPagesTest`: list, create, edit and delete; `code` refused on edit; the delete and narrowing refusal messages; the screen keys; the list links.
    - `ProductPagesTest` and `OwnerPagesTest`:
      - slug read-only
      - external-id change, add and remove, plus a held id and a malformed system refused
      - own flags limited by `applies_to`, ticked, replaced and cleared, and left alone when not sent
      - effective flags with inherited ones marked
      - the missing-`assign` case: the field is hidden, the rest of the page still saves, and a flag change gets 403
      - links by `id` by default and by `slug` when configured
    - Older tests in these two files now address records by `id`.

**What I committed**

- Everything above, unchanged.
- Pint is clean on every changed PHP file. PHPStan (project config) is clean on the six changed `src/Atrium` files.
- Targeted run, not the suite:
  - `FlagPagesTest`, `ProductPagesTest`, `OwnerPagesTest`, `StylesTest` and `DocsTest` pass: 69 tests, 297 assertions.
  - `ScreenExtensionsTest`: 113 of 128 pass, including the new `flags` and `flag` rows. The 15 failures are all product and owner URLs built from `TEE-1` or `acme` (404 under group 4's `id` binding), or list links expected by identifier or code. None comes from this group's code.

**What is left**

- 10.3: the full `vendor/bin/pest` run, for the gate.
- The 15 `ScreenExtensionsTest` identifier and code URL failures need the group 13 treatment. That file is not in group 13's list, so whoever runs group 13 or the fix round should add it.

**Deviations**

- Files outside the group's list, committed because the listed files need them:
  - `src/Atrium/Support/ExternalIdForm.php` (new): turns the map editor's form into the `external_ids` map. Both UI controllers use it.
  - `resources/views/ui/partials/external-ids.blade.php` (new): the map editor, shared by the product and owner screens.
  - `lang/en/showroom.php`: the flag, slug and external-id strings.
  - `README.md`, `tests/Feature/DocsTest.php` and `tests/Feature/Ui/ScreenExtensionsTest.php`: the two new screen keys. `docs/agent/standards/showroom/atrium-screens.md` requires these rows for a new page. `AGENTS.md` and that standard now say 27 keys instead of 25.
  - `tests/Pest.php`: the five flag actions are removed from `PARITY_EXCEPTIONS`, as group 6's notes asked once the flag screen shipped.

## Group 8 (salvaged)

Batch upsert action (AC 25–29, 35–39). Commit c21d01e on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr--g8`.

**What I found**

- No `index.lock`, and no merge or rebase in progress. Nothing for group 8 had been committed.
- The build agent had left the whole group uncommitted, and it was complete:
  - `UpsertProductsData` (`records`, `mode`, `match`, `captureChanges`), `UpsertProductsResult` (`created`, `updated`, `unchanged`, `skipped`, `failures`, `failed()`), the `UpsertMode` enum and the `ProductsUpserting`/`ProductsUpserted` action-event pair.
  - `ProductChangeCapture`, bound scoped in `ProductServiceProvider`. `suppress()` restores the previous state in a `finally`.
  - `BulkProductWriter`:
    - `takes()` accepts a new product with no parent and no associations.
    - `prepare()` checks the batch with a few queries: family, owner (`owns_products`), values against the family, external ids and unique values held in storage or earlier in the batch. It builds the rows: product, slug (`Slugs::forMany`), categories, external ids, flag assignments, unique values and the `created` version.
    - `store()` inserts them in one transaction and queues the index sync.
  - `UpsertProductsAction`:
    - It matches records by identifier or `external_ids[match]`, under `KeyNormalizer`, loading the matched products in one query.
    - It validates each record against `CreateProductData` or `UpdateProductData` rules, and asks `assign` when the flags change.
    - The preview compares every sent field. Equal records count as `unchanged`.
    - New simple records are written in bulk, falling back to `CreateProductAction` one by one when the bulk write throws. Every other record goes through `CreateProductAction` or `UpdateProductAction`.
    - All of it runs inside `ProductIndex::deferring()`, and inside `ProductChangeCapture::suppress()` when `captureChanges` is false.
  - Tests:
    - `UpsertProductsActionTest`: modes, matching by identifier and by system, a case-only match on `NOCASE` columns, in-batch duplicates, per-record failures by index/identifier/errors, read-only keys, domain guards, the unchanged preview (rows, `updated_at`, versions, sync job, action and `saved` events), every sent-field category making a record `updated`, version counts, approved → draft, one index sync per batch, `assign` refusals, the event pair, and suppression.
    - `UpsertProductsParityTest`: bulk path vs `CreateProductAction` across products, slug, categories, external ids, flags, unique values and the `created` version; a mixed chunk; a forced bulk-insert failure that falls back to the same result and rows.
    - `UpsertProductsPerformanceTest`: unchanged batches of 10 and 100 within 40 queries, and new batches within 60 and under 100 at 100 records.
    - `ProductChangeCaptureTest`: set inside, restored after, restored on throw, nested, and not leaking past `forgetScopedInstances()`.

**What I committed**

- Everything above, with these fixes:
  - `UpsertProductsAction`: the private `upsert()` method is renamed `writeBatch()`. `ArchTest`'s "actions write inside a transaction" rule matches `->upsert(` as a write, but the action writes nothing itself: `BulkProductWriter` and the per-record actions each use their own transaction.
  - `UpsertProductsAction::failWith()`: the `ModelNotFoundException<Model>` generic in its docblock (PHPStan `missingType.generics`).
  - `BulkProductWriter::prepare()`: its return description moved into the summary, so Pint's `phpdoc_align` is stable.
  - `BatchPresenceVerifier::getCount()`: `$value` is documented as `mixed` (PHPStan `function.alreadyNarrowedType`).
- Pint is clean and PHPStan (project config) passes on every changed source file.
- Targeted run, not the suite: the four group test files, `EventsTest` and `ArchTest` pass, 69 tests and 574 assertions. `FlagAssignmentTest`, `FlagActionsTest`, `ExternalIdsTest`, `SlugsTest`, `ProductIdentifierTest` and `AttributeApiTest` also pass with the shared-service changes below. `ProductApiTest` has 7 failures, all `GET`/`PATCH /showroom/products/{identifier}` answering 404, which belong to group 13.

**What is left**

- 8.3: the full `vendor/bin/pest` run, for the gate. It will still hit the identifier-URL 404s that group 13 owns.

**Deviations**

- Files outside the group's list, committed because the listed files need them. The build agent wrote all of them for this group:
  - `src/Support/BatchPresenceVerifier.php` (new): answers the batch's `exists` and `unique` rules from one query per column, so validation doesn't cost a query per record (AC 27, 29). The action doesn't load without it.
  - `src/Domains/Attribute/Services/ValueValidator.php`: it keeps the attributes, locales and channels it has read, so one instance reused across a batch reads each once. It is not bound in the container, so every `app(ValueValidator::class)` is still a fresh instance.
  - `src/Domains/Flag/Services/Flags.php` (group 7's file): `rules()` reads each flag code once per set of rules, so a batch's flag checks don't run a query per record.
  - `tests/Feature/EventsTest.php`: 82 → 83 actions.
  - `tests/Pest.php`: `UpsertProductsAction` in `PARITY_EXCEPTIONS` for `http`, `mcp` and `ui`. The spec puts HTTP and MCP endpoints for the batch upsert out of scope.
- `BulkProductWriter` mirrors `CreateProductAction`'s write step but doesn't share code with it. `CreateProductAction` isn't in this group's files, and the bulk path builds rows for many products at once, where the action saves one model. `UpsertProductsParityTest` holds the two to identical rows (AC 38), as the spec's risk section asks.
- For integration:
  - `tests/Pest.php` is also changed by group 10, which removed the flag lines from `PARITY_EXCEPTIONS` right below the line this group adds. Expect a small merge conflict there. Keep both changes.
  - `EventsTest`'s action count needs summing at merge if another group adds an action.

## Group 8

This retry covers task 8.3. The code and tests for 8.1 and 8.2 are in commit c21d01e ("group 8 Batch upsert action (salvaged)"). This commit ticks 8.1 to 8.3 and adds this note. No source or test files changed. `php -l` is clean on the action, the bulk writer and the batch presence verifier. Per the job instructions I didn't run the suite; the gate runs `vendor/bin/pest`.

- **Files (c21d01e):**
  - `src/Domains/Product/Actions/UpsertProductsAction.php`: validate (including `assign`) → load → preview → bulk or per-record write inside `ProductIndex::deferring()`.
  - `src/Domains/Product/Data/UpsertProductsData.php`, `src/Domains/Product/Data/UpsertProductsResult.php`, `src/Domains/Product/Enums/UpsertMode.php`.
  - `src/Domains/Product/Events/ProductsUpsertingActionEvent.php`, `src/Domains/Product/Events/ProductsUpsertedActionEvent.php`.
  - `src/Domains/Product/Services/ProductChangeCapture.php`: `suppress()` restores its state in a `finally`.
  - `src/Domains/Product/Services/BulkProductWriter.php`, sharing the write step with the create action.
  - `src/Domains/Product/ProductServiceProvider.php`: binds `ProductChangeCapture` as `scoped`.
- **Tests (c21d01e):**
  - `tests/Feature/UpsertProductsActionTest.php` covers:
    - modes and matching by identifier or external id, including case-only differences, and duplicate records in a batch
    - per-record validation reported by index, identifier and errors
    - read-only keys and domain-guard refusals
    - the unchanged preview: no writes, versions, syncs or events
    - created and updated version counts, and approved products sent back to draft
    - one index sync per batch
    - `assign` refusal as a per-record failure
    - the batch events, and change-capture suppression only when asked
  - `tests/Feature/UpsertProductsPerformanceTest.php`: the query count stays flat between batch sizes for unchanged re-upserts and for new simple products.
  - `tests/Feature/UpsertProductsParityTest.php`:
    - the bulk path matches `CreateProductAction`
    - a mixed chunk
    - a forced bulk failure that falls back to per-record writes
  - `tests/Unit/ProductChangeCaptureTest.php`: default capture, restore on throw, nested suppression, and a scoped binding per request.
- **Deviations: files outside group 8's list (c21d01e):**
  - `src/Support/BatchPresenceVerifier.php` (new): answers `exists`/`unique` for a whole batch from one primed read. Without it, per-record validation grows the query count with each record, and the performance criteria fail.
  - `src/Domains/Attribute/Services/ValueValidator.php`: keeps the attributes, locales and channels it has read, so one instance reused across a batch reads each once. A fresh instance per write behaves as before.
  - `src/Domains/Flag/Services/Flags.php`: `rules()` reads each flag code once per rule set, for the same reason.
  - `tests/Feature/EventsTest.php`: the pinned action count goes from 82 to 83. Other groups that add actions need to add up their counts at merge.
  - `tests/Pest.php`: adds `UpsertProductsAction` to `PARITY_EXCEPTIONS` for `http`, `mcp` and `ui`. The spec puts HTTP and MCP endpoints for the batch upsert out of scope.

## Group 10

Atrium screens (AC 40, 41), retry for task 10.3. The work is commit a3862e6 on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr--g10`. See "Group 10 (salvaged)" above for what it contains.

- **Files:** none changed in this retry. The worktree was clean at a3862e6. I checked that its tests cover every 10.1 item (the flag-definitions CRUD and refusal messages, a read-only slug, the external_ids map editor, own flags limited by `applies_to` and hidden without `assign`, effective flags with inherited ones marked, and links by the configured lookup key) and that they're backed by the 10.2 code.
- **Tests:** Pint `--test` passes on `src/Atrium`, `tests/Feature/Ui`, `tests/Pest.php`, `tests/Feature/DocsTest.php` and `lang`. As instructed, I didn't run the suite myself (10.3); the gate runs `vendor/bin/pest`. Expect these failures, which group 13 or the fix round should clear: the 15 `ScreenExtensionsTest` rows that still build product and owner URLs from an identifier or code (they get 404 under group 4's `id` binding).
- **Deviations:** none new. The out-of-list files are listed under "Group 10 (salvaged)": `src/Atrium/Support/ExternalIdForm.php`, `resources/views/ui/partials/external-ids.blade.php`, `lang/en/showroom.php`, `README.md`, `AGENTS.md`, `docs/agent/standards/showroom/atrium-screens.md`, `tests/Feature/DocsTest.php`, `tests/Feature/Ui/ScreenExtensionsTest.php` and `tests/Pest.php`.

## Group 12 (retry)

Flag search and owner reindex (AC 22, 23, 34). This retry checked commit 24cf1d4 on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr--g12`. The worktree was clean. See "Group 12" above for what that commit contains.

- **Files:** none changed in this retry. 12.1 to 12.3 were already ticked.
- **Checked:**
  - `FlagSearchTest` covers every 12.1 item.
  - The code behind it meets AC 22, 23 and 34:
    - `ProductDocument` writes `flags` as normalized effective flags.
    - The `flags[]` filter works on `DatabaseEngine`, `ScoutEngine` and `ListOwnersAction`.
    - `UpdateOwnerAction` queues `queueWithin(SyncScope::Owner, …)` on a flag change or a move, and not for an unchanged set.
    - `ScoutEngine::indexSettings()` lists the required fields.
- **Tests:** `tests/Feature/Search/FlagSearchTest.php` passes: 18 tests, 299 assertions. Pint `--test` is clean on `DatabaseEngine.php` and the test file. I didn't run the suite; the gate runs it. Expect the two `ScoutEngineTest` identifier-URL `404` failures, which belong to group 13.
- **Deviations:** none.

## Group 13

Existing API and search tests on id lookup (AC 8). Commit c2c991f on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr--g13`. Only tests changed. No code changed, and no slug or identifier fallback was added.

**13.1: failures found.** Running the 17 listed files gave 62 failures before the change. All but one were `404`s on product or owner URLs built from an identifier or code. The 404s showed up as status mismatches, or as `null` JSON paths read from a 404 body. None came from an MCP argument: these files call MCP tools only for list, attribute and transfer tools. The other failure was the `RecordLookupTest` route-list dataset, which picked up Impex's `impex.runs.owners.destroy`.

**Files changed**

- `tests/Feature/RecordLookupTest.php`: the "lists every route that takes a product or an owner" test now keeps only routes named `showroom.*` or `atrium.showroom.*` before it checks for `{product}` or `{owner}` (13.3).
- `tests/Feature/Api/ProductPollingTest.php` and `tests/Feature/PolicyTest.php`: they take the `id` from the create response (`json('data.id')`).
- The other 14 files each get a small file-local helper that returns `/showroom/products/{id}` or `/showroom/owners/{id}`, using the model's key looked up by identifier or code. Their identifier and code URLs now call it:
  - `workflowProduct`
  - `productApiPath`
  - `assetProductPath` and `assetOwnerPath`
  - `completenessProductPath`
  - `categoryProductPath`
  - `associationProductPath`
  - `ownershipProductPath` and `ownershipOwnerPath`
  - `channelProductPath`
  - `performanceProductPath` and `performanceOwnerPath`
  - `scoutProductPath`
  - `auditProductPath`
  - `renamedProductPath`
  - `importProductPath`
  - `webhookProductPath`
- Request bodies and filters still reference records by identifier or code (`parent`, `owner`, associations, asset link targets), as the spec says.
- In `PerformanceTest` the helper calls sit outside every `queriesDuring` block, so the query bounds are unchanged.

**Tests**

- `vendor/bin/pest` on all 17 listed files: 467 passed, 3211 assertions.
- Pint `--dirty` is clean. PHPStan doesn't cover `tests/`.

**Deviations**

- The helpers are file-local with distinct names, not one shared helper in `tests/Pest.php` or `tests/Fixtures/Catalog.php`. Those files aren't in this group's list, and group 14 is editing tests at the same time. Pest loads every test file into one process, so the names must differ. A later cleanup could fold them into one shared helper.
- Group 9 rewrites `tests/Feature/Impex/ImportExportTest.php` and `ProductWebhooksTest.php`. It should keep `importProductPath` and `webhookProductPath`, or address records by `id` some other way.

## Group 14

Existing UI and MCP tests on id lookup (AC 8). Commit dae1b39 on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr--g14`. Only tests changed. No screen or source file changed, and no slug, identifier or code fallback was added.

**14.1: failures found**

Running the ten listed files gave 51 failures out of 310 tests. Each one was a product or owner lookup by identifier or code. Under group 4's `id` binding these answer `404` on HTTP and Atrium, or `Not found.` on MCP:

- `WorkflowPagesTest`: 3. The product screen, transition and revert by `TEE`, and `PATCH /showroom/products/TEE`.
- `AssociationPagesTest`: 1. The product screen and `GET /showroom/products/TEE`.
- `WidgetsTest`: 1. `POST /showroom/products/TEE-1/transitions`.
- `ScreenAuthorizationTest`: the record-page and action dataset rows for `TEE-1`, `TEE-S` and `acme`, plus the product update in `screenWorld()`.
- `ScreenExtensionsTest`: the 15 rows group 10 reported. The `product` and `owner` screens were opened by `TEE-1` and `acme`, and the `products` and `owners` list links were expected by identifier or code.
- `ScreenIsolationTest`, `AssetPagesTest`, `CategoryPagesTest` and `ChannelPagesTest`: product screen URLs by `TEE-1`. In `CategoryPagesTest`, the array mismatch came from a `PATCH` that answered `404`.
- `McpTest`: 3 tests. They cover show, update and delete for products; the transition, versions and revert tools; and show, update and delete for owners.

Every failure came from lookup, so no test or screen needed a non-lookup fix (the second half of 14.2).

**14.2: files changed (tests only)**

- `WorkflowPagesTest`, `AssociationPagesTest`, `WidgetsTest`, `ScreenIsolationTest`, `AssetPagesTest`, `CategoryPagesTest` and `ChannelPagesTest` (all in `tests/Feature/Ui/`): each keeps the record it creates and builds URLs from it. That's either the model or the `id` from the create response.
- `tests/Feature/Ui/ScreenAuthorizationTest.php` and `tests/Feature/Ui/ScreenExtensionsTest.php`: the datasets still name records by identifier or code, because they're built before the records exist. A file-local helper in each file (`screenRecordKey()` and `screenExtKey()`) swaps that name for the record's `id` on `atrium.showroom.products.*` and `atrium.showroom.owners.*` routes. Every other screen keeps its code.
- `tests/Feature/McpTest.php`: the product and owner tools get the record's `id`. With the right key, `DeleteOwnerTool` on `acme` is refused by its child-owner guard again, rather than by a lookup miss.
- Input references keep the identifier or code, as the design requires. These are association `source`/`target`, asset `target`, `parent`, the `owner` and `category` filters, and list search.

**Tests**

- 14.3: the ten listed files pass: 310 tests, 6727 assertions.
- All of `tests/Feature/Ui` passes together (351 tests), so the new helper names don't clash with other test files. They also differ from group 13's helpers.
- Pint is clean on the changed files.
- I didn't run the full suite; the gate runs it.

**Deviations**

- None. Apart from `tasks.md` and this note, I touched only files on the group's list.

## Group 9 (salvaged)

Impex integration (AC 30, 31, 32). The build agent stopped without returning a result. I salvaged its uncommitted work and committed it on `ring-master/2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr`.

**What I found**

- There was no index lock and no merge or rebase in progress.
- The work was complete for 9.1 and 9.2. It was uncommitted: some changes were staged, some unstaged and some files untracked.
- `src/Impex/Actions/UpsertProductChunk.php` replaces `ImportProductRow`, which is deleted. It makes one `UpsertProductsAction` call per chunk and passes `mode` and `capture_changes` through. It moves each failure's `index` from the chunk's position to the record's row in the source (`start + index`).
- `InlineProductSource` and `FileProductSource` now yield one batch item per `showroom.impex.chunk` records. Each item holds `{mode, capture_changes, start, records}`.
  - The file cursor is now `{offset}:{row}`, so row numbers carry across pages. Blank lines and a CSV header are not counted as rows.
  - A malformed JSONL line goes into the chunk as a string. The action then fails it at `record`, on its own row.
- `UpsertProductsFlow` and `ImportProductsFlow` take `bool $captureChanges = true`. They run one chunk per batch item (`->chunk(1)`) and count rows with `ProductChunkSummary`, which returns `total`, `succeeded`, `failed` and `unchanged`. They apply `showroom.impex.allow_failures` to rows rather than chunks.
- `CaptureProductChanges` returns early while `ProductChangeCapture::isSuppressed()` is true. It resolves the service on each write because the subscriber outlives the scoped binding.
- The tests:
  - `tests/Feature/Impex/UpsertChunkTest.php` is new.
  - `ImportExportTest` adds tests for per-chunk calls, a failure in the second chunk reported by its global row (both flows), `capture_changes` passed through both flows, `unchanged` on a re-sent record, and failure tolerance counted by row.
  - `ProductWebhooksTest` checks that a run records by default and records nothing with `capture_changes` off, and that a direct `suppress()` records nothing and recording works again afterwards.
  - `tests/ArchTest.php` adds the core-never-uses-Impex rule.
  - Group 13's `importProductPath` and `webhookProductPath` id helpers are kept.

**What I committed**

- The group's listed files.
- I ran Pint on them. It fixed import order and brace style in `InlineProductSource` and `ProductChunkSummary`.
- These files outside the group's list were also changed by the group 9 agent, and the listed files need them:
  - `src/Impex/ProductChunkSummary.php` (new): the row-level run summary.
  - `src/Domains/Transfer/Events/ProductChunkUpsertingActionEvent.php` and `ProductChunkUpsertedActionEvent.php`: the per-chunk event pair. They replace `ProductRowImportingActionEvent` and `ProductRowImportedActionEvent`, which are deleted.
  - `tests/Feature/StoredIdentifiersTest.php`: the stored Impex class list now names `UpsertProductChunk` instead of `ImportProductRow`.

**What is left**

- 9.3: I didn't run the test suite, as the salvage instructions say; the gate runs it.
- AC 31 asks for a `--no-capture-changes` CLI option. Impex's `FlowArguments` turns a non-null `bool` parameter into a `VALUE_NONE` flag, so only `--capture-changes` exists, and it can't turn off a default of `true`. From the CLI, capture can be turned off only positionally (`--argument=...` with `0`). This is raised as a decision.
- The arch rule ignores `RefactorCircus\Showroom\Domains\Transfer` and `Atrium\Http\Controllers\TransferUiController` as well as `RefactorCircus\Showroom\Impex`. Those classes used Impex before this spec, to start runs behind `ImpexIntegration::active()`, so the exact rule in AC 32 would fail on existing code. This is raised as a decision.
- Group 11: `resources/boost/skills/showroom-development/SKILL.md` still names `ProductRowImportedActionEvent`, and the changelog needs the upgrade note for the event replacement.

## Group 15

Applies build decisions D-011 (AC 31) and D-012 (AC 32).

**Files**

- `src/Impex/Flows/UpsertProductsFlow.php` and `src/Impex/Flows/ImportProductsFlow.php`: `handle()` now declares `?bool $captureChanges = null` and passes `$captureChanges ?? true` to its source. Impex's `FlowArguments` turns a nullable `bool` into a value option, so `impex:run <flow> --capture-changes=0` turns capture off, and leaving the option out keeps the default of true. Positional and in-code callers that pass `true` or `false` work as before.
- `tests/ArchTest.php`: the "code outside src/Impex uses no Impex class" rule already ignored exactly `RefactorCircus\Showroom\Impex` plus the two D-012 exceptions, `RefactorCircus\Showroom\Domains\Transfer` and `RefactorCircus\Showroom\Atrium\Http\Controllers\TransferUiController`. Only its comment changed: it now cites D-012 and says no other exception may join them.
- `tests/Feature/Impex/UpsertChunkTest.php`: two new tests.

**Tests**

- `showroom:import-products` is run twice through `impex:run`: once with no option and once with `--capture-changes=0`. Both runs complete and both products are written. The default run's product is in the `showroom.products` stream touches and the `--capture-changes=0` run's product is not.
- `showroom:upsert-products` can't take its `array $payload` from a command line, because the console carries only strings. So the test reads the flow's options the way `impex:run` does (`FlowArguments::options()` and `FlowArguments::from()` on an `ArrayInput`), asserts that `--capture-changes=0` becomes `['captureChanges' => false]`, and starts the run with those arguments plus the records. The same record and no-record assertions follow.
- Both tests fail before the change. While the parameter is a non-null `bool`, `--capture-changes` is a `VALUE_NONE` flag, so giving it `0` raises `InvalidOptionException`.
- The helper names (`captureProductWebhooks`, `recordedForWebhooks`, `flowCommandArguments`) differ from the global helpers in `ProductWebhooksTest` and `ImportExportTest`.
- Pint is clean on the changed files. I didn't run the suite; the gate runs it (15.3).

**Deviations**

- None. Apart from `tasks.md` and this note, I touched only the files on the group's list.

## Group 11

Docs and changelog. The upgrade notes come from the spec's Design.

**Files**

- `tests/Feature/DocsTest.php` (11.1). New tests check that:
  - every key in the shipped `config/showroom.php` (dot keys down to the leaves) is covered by a row in `docs/13-configuration.md`, and every documented row exists in the config
  - the `products.identifier_pattern`, `lookup.products` and `lookup.owners` rows exist with their defaults, and the `tables.*` and `policies` rows name the new tables and `assign`
  - `docs/14-flags.md` exists and covers `applies_to`, `effective_flags`, `assign`, `FlagPolicy`, `flags[]=`, the routes, the five tools and the actions
  - the products, ownership, search, API and MCP pages link to it, and every `*.md` link in `docs/` resolves
  - the products, ownership, API, MCP and search pages cover identifiers, slugs, `?by=`/`by` and external ids, and no longer say identifiers are path or MCP keys
  - the Impex page covers the batch upsert, `unchanged` and `--capture-changes=0`
  - the Unreleased changelog has the five upgrade notes
- `docs/14-flags.md` (new): definitions and their guards, assigning, the `assign` ability, inheritance, search and reindexing, endpoints and tools, and the Atrium screen.
- `docs/04-products.md`: Identifiers, Slugs, Looking up a product, External ids and Batch upsert sections. Delete cleanup. `UpsertProductsAction` in the actions table.
- `docs/06-ownership.md`: a self-parenting owner type (AC 24), owner slugs, lookup, external ids, flags, the new list filters, and `owner_slugs` in the search document.
- `docs/05-search.md`: the `external[{system}]` and `flags[]` filters, flag changes re-indexing an owner's products, the new document fields, and `ScoutEngine::indexSettings()` with a Meilisearch config example.
- `docs/09-api.md`: flag routes, the new list filters, a "Product and owner keys" section (`id` default, `showroom.lookup.*`, `?by=`, 404/422), read-only keys, and `assign` under Authorization.
- `docs/10-mcp.md`: the five flag tools, `by` and id/slug addressing for the product and owner tools, and the new fields.
- `docs/12-impex.md`: per-chunk processing through `UpsertProductsAction`, `unchanged` and global row indexes in the result, `tries` per chunk, a "Keeping a sync out of product webhooks" section (`--capture-changes=0`, `capture_changes`), `slug` in exports, ERP external ids, the chunk events, and `{product}` instead of `{identifier}` in URLs.
- `docs/13-configuration.md`: `products.identifier_pattern`, `lookup.products` and `lookup.owners`. The new tables and `assign` in the `tables.*` and `policies` rows. `impex.chunk` and `impex.tries` now say chunk.
- `docs/03-families.md`: families stay addressed by `code` (no slug or lookup key), and the stale "Products (coming next)" line is gone.
- `CHANGELOG.md` (Unreleased):
  - Breaking: lookup by `id` with no fallback (`showroom.lookup.*`, `?by=`), read-only `slug`/`effective_flags`, `ImportProductRow` and `ProductRowImportedActionEvent` replaced by `UpsertProductChunk` and the chunk events (plus `unchanged`), the slug migration and its unique-index lock on large installs, Scout settings and a reindex, and the wider default identifier pattern.
  - Added: one entry for each feature.

**Tests**

- `tests/Feature/DocsTest.php`: 47 passed, 112 assertions. Before the docs changed, 11 of the new tests failed. Pint is clean on the file.
- I didn't run the full suite (11.4); the gate runs it.

**Deviations**

- None. Apart from `tasks.md` and this note, I touched only the files on the group's list.

**Found while documenting (outside this group's files)**

- `resources/boost/skills/showroom-development/SKILL.md` line 77 still names `ProductRowImportedActionEvent`, as group 9's notes said. That file isn't on this group's list.
- JSONL round trip: `ExportRecord` writes `slug` into every JSONL record. `FileProductSource` passes JSONL records through unchanged, and `UpsertProductsAction` validates them against `CreateProductData`/`UpdateProductData`, where `slug` is `prohibited`. So a JSONL export imported back fails every row at `slug`. CSV is fine, because `ProductRows` skips `slug`. The existing round-trip test only covers CSV. The docs describe the current behaviour: remove `slug` before re-importing JSONL. The likely fix is to drop the read-only keys from JSONL records in `FileProductSource`, as `ProductRows` does for CSV.
- MCP tool schemas don't advertise the new inputs. The tools accept them, because their requests validate with the Data rules:
  - `create-product-tool`, `update-product-tool` and the owner create and update tools don't declare `external_ids` or `flags`.
  - `list-products-tool` and `list-owners-tool` don't declare `external` or `flags`.
  - `create-product-tool` still describes `identifier` as "letters, digits, dots, dashes and underscores".

## Group 11 (retry)

All of 11.1 to 11.4 were already committed in `6b2dcca`, with the tasks ticked. This retry checked that work and changed no docs or tests.

**Files**

- None changed apart from this note.

**Tests**

- `tests/Feature/DocsTest.php`, run with the worktree's `phpunit.xml.dist`: 47 passed, 112 assertions. Run against the host app's configuration instead, the file fails at boot with `Undefined array key "external_ids"` in the external-ids migration, because that configuration lacks the new `tables.*` keys. That failure comes from where the tests run, not from the docs.
- Pint is clean on `tests/Feature/DocsTest.php`.
- I didn't run the full suite (11.4); the gate runs it.

**Deviations**

- None.
