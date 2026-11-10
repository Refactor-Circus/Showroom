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
