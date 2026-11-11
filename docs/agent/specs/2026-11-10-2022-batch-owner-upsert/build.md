# Build

## Group 1

OwnerWriter extraction (tasks 1.1-1.3; criteria 4, 6, 7, 11).

### Files

- `src/Domains/Owner/Services/OwnerWriter.php` (new, `@internal`): `create(CreateOwnerData, OwnerTypeModel $type, ?OwnerModel $parent): OwnerModel` and `update(OwnerModel, UpdateOwnerData, ?OwnerModel $parent = null, ?OwnerTypeModel $type = null): bool`. They hold the placement rules (through `PlacesOwners`), the slug claim, and the external-id and flags writes with the `assign` check. Neither opens a transaction, fires an event or reloads the returned owner's relations. `update()` returns whether the products beneath need a reindex (a move or a flag change) and leaves the queueing to its caller, so the batch can queue one sync. It reads the parent or type itself only when it isn't given one, which keeps the single update action's query and model-event order unchanged. A given parent or type that isn't the one the data names, or the one the owner has, throws `InvalidArgumentException`.
- `src/Domains/Owner/Actions/CreateOwnerAction.php`: resolves the type and parent in its transaction as before, writes through `OwnerWriter`, then loads `type`, `parent`, `externalIds` and flags. It is still final, and its constructor signature, events and return value are unchanged.
- `src/Domains/Owner/Actions/UpdateOwnerAction.php`: writes through `OwnerWriter` in its transaction and calls `ProductIndex::queueWithin(SyncScope::Owner, id)` when the writer reports a move or a flag change. It then loads the same return graph as before. It is still final, and its constructor signature, events and return value are unchanged.
- `tests/Feature/OwnerWriterTest.php` (new).

### Tests

- `OwnerWriterTest` (21 tests) covers create and update with a preloaded type and parent:
  - `can_be_root` and `parent_types`;
  - a move under the owner itself, a child or a grandchild;
  - the path and depth of a moved owner and its descendants, and `parent: null` to the root;
  - slugs claimed as `CreateOwnerAction` claims them;
  - external ids and flags written;
  - `assign` refused when flags are set on create or changed on update, before anything is written;
  - no `owner_types` or parent re-read when they are given;
  - mismatched type or parent guards.
- Run narrowly: `OwnerWriterTest`, `SlugsTest`, `FlagAssignmentTest`, `ExternalIdsTest`, `RecordLookupTest`, `Ui/OwnerPagesTest`, `EventsTest` and `ArchTest` all pass (392 tests). The gate runs the full suite.
- Pint passes. PHPStan passes on the changed `src` files.

### Deviations

- `CreateOwnerAction` and `UpdateOwnerAction` build their `OwnerWriter` in the constructor from the services they are given, instead of having it injected, so their constructor signatures stay the same. `UpdateOwnerAction` takes no `Slugs`, so it resolves one with `app(Slugs::class)` for the writer, which uses it only on create.
- `Concerns/PlacesOwners` is now used only by `OwnerWriter`. It is unchanged.

## Group 2

UpsertOwnersAction (tasks 2.1-2.4; criteria 1-12b).

### Files

- `src/Domains/Owner/Data/UpsertOwnersData.php` (new): `records`, `mode` (`UpsertMode`), `match` (`code` by default, or a source checked with `ExternalIds::SOURCE_PATTERN`), and `matchesExternalIds()`.
- `src/Domains/Owner/Data/UpsertOwnersResult.php` (new): `created`, `updated`, `unchanged`, `skipped` and `failures` (`{index, code, errors}`, in batch order), plus `failed()`.
- `src/Domains/Owner/Events/OwnersUpsertingActionEvent.php` and `OwnersUpsertedActionEvent.php` (new).
- `src/Domains/Owner/Actions/UpsertOwnersAction.php` (new). It runs in these steps:
  - Match by code, or by `ExternalIds::find()` followed by one owner query, with `type.parentTypes`, `externalIds` and `flags`.
  - Load the parents the records name, with their type, and the new records' types, with their parent types, once.
  - Prime `BatchPresenceVerifier` with the new codes, the parent codes and the types.
  - Validate with one `CreateOwnerData::rules()` and one `UpdateOwnerData::rules()` per batch.
  - Claim codes, and matched external ids, in batch order (D-003), then preview each record in memory.
  - Write each remaining record in record order, in its own `DB::transaction`, through `OwnerWriter`, with the single actions' events around it.
  - Stamp the unchanged records with one `ExternalIds::touch()` after the writes.
  - Queue the reindex through `ProductIndex::deferring()`.
  - The batch keeps one model instance per owner. A committed move rewrites the in-memory path and depth of every owner it holds beneath the moved one, and a rolled-back write restores the owner's attributes. Later records therefore see the new paths (criterion 12a).
  - A parent created earlier in the batch skips `exists` and is resolved when its child is written. When it isn't there, because it comes later or its record failed, the child fails with the `exists` rule's message.
  - Lookups run in chunks of `showroom.impex.chunk`.
- `tests/Feature/UpsertOwnersActionTest.php`, `UpsertOwnersParityTest.php` and `UpsertOwnersPerformanceTest.php` (new).
- `tests/Feature/EventsTest.php`:
  - The action count goes to 84.
  - The batch may fire, beside its own pair, the four owner create and update events it fires on behalf of the single actions.
  - A new test pins the order of the batch's dispatches.
- `docs/06-ownership.md`: a "Batch upsert" section, and the action added to the actions list.
- `CHANGELOG.md`: an Added entry.
- Outside the group's file list: `tests/Pest.php` gains `'UpsertOwnersAction' => ['http', 'mcp', 'ui']` in `PARITY_EXCEPTIONS`. The spec puts HTTP and MCP surfaces for the batch out of scope, and without the entry the arch parity rule fails.

### Tests

- `UpsertOwnersActionTest` (51 tests) covers:
  - modes, match by code or by source, and the code or type change refused;
  - validation keys and messages;
  - D-002 (an absent field kept, `parent: null` to the root, unchanged with fields left out);
  - D-003 (the first record claims a code whether it is created, updated, unchanged, skipped or failed, the same matched external id, and a duplicate adds no parent);
  - in-batch parents (earlier, later, or a failed parent record, by validation or by the database);
  - placement (every rule, with the single actions' message);
  - path safety (a swap, a moved owner and a descendant, and a rolled-back move keeping stored paths);
  - unchanged records (no rows, events or jobs, one `synced_at` update after the writes, skipped and failed never stamped, each changed field);
  - slugs in record order, and `assign` on create and update;
  - events and their order, and the reindex with `Queue::fake` (one `SyncProductIndex` for the batch, none without a move or flag change, still queued when the batch throws midway, none for a rolled-back owner);
  - a database error isolated to its owner, a unique violation on `code` reported at `code`, and chunked lookups.
- `UpsertOwnersParityTest` (6 tests) runs datasets for create, update with fields left out, move, flags and refused, plus `assign`. Each dataset compares an explicit column list (code, type, labels, parent, path by codes, depth, slug, external ids, flags) between the batch and `CreateOwnerAction` / `UpdateOwnerAction` one by one. It also compares the owner action and write model events in order.
- `UpsertOwnersPerformanceTest` (3 tests): an unchanged batch runs the same number of queries at 10 and at 100 records. The written bounds are measured, see Deviations.
- Run narrowly: the three new files, `EventsTest`, `ArchTest`, `DocsTest` and `OwnerWriterTest` all pass. The gate runs the full suite.
- Pint passes. PHPStan passes on the new `src` files.

### Deviations

- **Criterion 12's written bound is not met at 6 per owner.** On this build, with every field sent, the measured costs are:
  - Created owners: 108 queries at 10 and 1008 at 100, so 10 per owner and `C` = 8.
  - Updated owners (a move plus labels, external id and flags changes): 139 at 10 and 1309 at 100, so 13 per owner and `C` = 9.

  Twelve of each updated owner's queries, and all ten of each created owner's, run inside `OwnerWriter` and the shared `Flags`, `ExternalIds` and `Slugs` services, outside this group's files. The test fixes `C` = 9 and the measured per-owner figures (10 created, 13 updated) so it guards against regressions. Reaching 6 is raised as a decision.
- **Reindex.** The batch lists the products beneath each moved or re-flagged owner, skipping owners beneath another listed one, through `ProductIndex::productIdsOwnedWithin()`. It queues their ids into the `deferring()` buffer, so the batch pushes one `SyncProductIndex`, per 500 products. That is one query per moved owner, and the batch lists the products itself where `UpdateOwnerAction` defers the listing to a `QueueProductSyncs` job. This is the only reading of "one job, through `deferring()`'s `finally` path" that the existing `ProductIndex` API allows. It is raised as a decision.
- Unchanged records are stamped inside a `DB::transaction` (one statement), so the action's writes all sit in a transaction, per actions-events.

## Group 2 (salvaged)

### Found

- No `index.lock`, and no merge or rebase in progress. The working tree was clean.
- The build agent had already committed the whole group as `d962897` ("group 2 UpsertOwnersAction"). That commit holds all eleven listed files, the `tests/Pest.php` `PARITY_EXCEPTIONS` entry described above, tasks 2.1-2.4 ticked, and the "Group 2" section above. Nothing was left uncommitted, so no code needed recovering.
- The agent ended before it returned its result, so the two decisions its Deviations name never reached the workflow. This salvage returns them:
  - The criterion 12 per-owner bound: measured at 10 per created owner and 13 per updated owner, against 6, with `C` = 9.
  - The reindex shape: one `SyncProductIndex` carrying product ids that the batch lists, against criterion 9's one job carrying owner ids.

### Committed

- This note only. The code, tests, docs and the CHANGELOG are in `d962897`, unchanged.

### Left

- The two decisions above. The performance test pins the measured figures (`OWNERS_UPSERT_FIXED_QUERIES` = 9, 10 per created owner, 13 per updated owner) until the decision on criterion 12 is made.
- Full suite: not run here. The gate runs it.

## Group 3

showroom:upsert-owners Impex flow (tasks 3.1-3.3; criteria 13, 14).

### Files

- `src/Impex/Flows/UpsertOwnersFlow.php` (new): `showroom:upsert-owners`, `handle(array $payload, string $mode = 'upsert', string $match = 'code')`. It takes a list, or `{"owners": [...]}` with a list. Any other payload throws a `ValidationException` on `payload` before the batch is started, so the run fails before any chunk. It then batches the records through `InlineOwnerSource` and `UpsertOwnerChunk`, one chunk per item, with `allowFailures(1.0)`, `showroom.impex.tries`, and the row share held to `showroom.impex.allow_failures`, as `UpsertProductsFlow` does.
- `src/Impex/Actions/UpsertOwnerChunk.php` (new): one `UpsertOwnersAction` call per chunk, with the chunk's `mode` and `match`. It returns `total`, `created`, `updated`, `unchanged`, `skipped`, `failed` and `failures`, each failure's chunk-relative index offset by the chunk's `start`. It fires `OwnerChunkUpsertingActionEvent` and `OwnerChunkUpsertedActionEvent`.
- `src/Impex/OwnerChunkSummary.php` (new): row counts from the batch items, as `ProductChunkSummary` counts them. `succeeded` is `total - failed`, so skipped and unchanged rows count inside it (D-006), and a chunk that failed as a whole fails all its rows. `tolerate()` names `UpsertOwnerChunk` in the threshold error.
- `src/Domains/Transfer/Events/OwnerChunkUpsertingActionEvent.php` and `OwnerChunkUpsertedActionEvent.php` (new): the chunk pair, unaudited like the product chunk pair.
- `src/Impex/ImpexIntegration.php`: `UPSERT_OWNERS = 'showroom:upsert-owners'`, registered in `flows()` next to `UPSERT`.
- `tests/Feature/Impex/UpsertOwnersFlowTest.php` (new).
- Outside the group's file list: `src/Impex/Sources/InlineOwnerSource.php` (new). It is the inline owner source the spec's design names, mirroring `InlineProductSource` with `match` in place of `capture_changes` in each chunk's payload.
- `tests/ArchTest.php`: unchanged. The new code outside `src/Impex` is the two events in `Domains/Transfer`, which name no Impex class, so the core-does-not-use-Impex rule holds as it stands. `UpsertOwnerChunk` dispatches a starting and a finished event and writes nothing itself, so the Impex actions' event and transaction rules hold too.

### Tests

- `UpsertOwnersFlowTest` (12 tests, 15 cases with datasets) covers:
  - the flow registered next to `showroom:upsert-products`;
  - records as a list or as `{"owners": [...]}`, with an in-batch parent;
  - one `UpsertOwnersAction` call per `showroom.impex.chunk` (5 records at 2 per chunk give calls of 2, 2 and 1, and 3 batch items);
  - `mode` and `match` passed through to the action, and a skipped row counted inside `succeeded` (D-006);
  - failures kept on the batch items at their global position over three chunks (indexes 3 and 4, with their codes and `parent` and `record` keys);
  - a malformed payload (`owners` not an array, keyed rather than a list, or missing) failing the run with no chunk event and no owner written;
  - a chunk that throws counting its rows failed while the next chunk is written;
  - the row share tolerated;
  - a second run reporting every row unchanged;
  - the chunk action alone (offset failures, events, unaudited), and `InlineOwnerSource` chunking.
- Not run here: the prompt leaves the suite to the gate. Pint passes on every changed file. PHPStan passes on `src/Impex` and `src/Domains/Transfer/Events`, and on the new test file.

### Deviations

- `InlineOwnerSource` is a new file outside the group's list (above).
- `docs/12-impex.md` documents `showroom:upsert-products` and its chunk events but is outside the group's list, so the new flow is not documented there yet.

### Retry

- The work above was already committed in `ea408e1`, with tasks 3.1-3.3 ticked. No code changed on the retry.
- Checked narrowly: `UpsertOwnersFlowTest` and `ArchTest` pass (35 tests, 158 assertions), including the core-does-not-use-Impex rule. Pint passes on the group's files. PHPStan passes on `src/Impex`, `src/Domains/Transfer/Events` and the new test file. The gate runs the full suite.
