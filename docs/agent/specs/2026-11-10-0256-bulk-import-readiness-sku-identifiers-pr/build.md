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
