# Batch owner upsert

## Goal

Host apps that sync owners from another system (the `secret` app's catalog import, later ERP syncs) can create and update a batch of owners in one call. The cost must not grow per unchanged record, and must stay a small constant per written one, instead of about 14 queries per owner through `CreateOwnerAction` and `UpdateOwnerAction`. The Impex flow gives ERP and webhook bulk loads the same path, mirroring `showroom:upsert-products`.

## Acceptance criteria

### Action

1. `UpsertOwnersAction::execute(UpsertOwnersData)` takes:
   - `records`: owner records in API shape (`code`, `type`, `labels`, `parent` as an owner code, `external_ids`, `flags`);
   - `mode`: `upsert` (default), `create` or `update`, as `UpsertMode` does for products;
   - `match`: `code` (default), or an external-id source whose ids match records to stored owners.

   It returns `UpsertOwnersResult` with `created`, `updated`, `unchanged`, `skipped` and `failures` (each `{index, code, errors}`, in batch order). In `create` mode a matched record is skipped; in `update` mode an unmatched one is. `skipped` is a count only (D-006).
2. A record that is not an object fails with `record`. Every other record is validated with `CreateOwnerData`'s rules when it creates, and `UpdateOwnerData`'s when it updates. A matched record that sends a different `code` or `type` from the stored owner's fails, because those never change. On an update, a field the record leaves out keeps its stored value, and an explicit `parent: null` moves the owner to the root (D-002).
2a. **Duplicates (D-003).** The first record to use a code, or a matched external id, claims it in batch order, whatever that record's outcome: written, unchanged, skipped or failed. A later record using it fails with `code` as a duplicate, writes nothing, and adds nothing to the in-batch parent map. Tests cover the same code, the same matched external id, and a duplicate of a record that failed or was skipped.
3. A record's `parent` may name an owner stored before the batch, or one created by an earlier record in the same batch. A record whose parent comes later in the batch fails as if the parent did not exist.
4. Placement follows the single actions:
   - a root must have a type with `can_be_root`;
   - a parent's type must be in the type's `parent_types`;
   - a move under the owner itself or one of its descendants is refused.

   A record that breaks a rule fails, and the owner is not written. Each failure carries the error key of the rule it broke, with the single actions' message: `record` (not an object), `code` or `type` (changed on a matched owner), `parent` (unknown or later-in-batch parent, a placement rule, a refused move) and `flags` (the `assign` refusal). Validation failures keep the key of the field `CreateOwnerData` or `UpdateOwnerData` rejects.
5. A matched record whose sent fields equal the stored owner's (labels, parent, external ids, flags) is `unchanged`. A field absent from the record is left out of the comparison; an explicit `parent: null` compares as the root (D-002). Tests: an absent `parent` leaves the parent alone, `parent: null` moves the owner to the root, and a record with absent fields can be `unchanged`. It writes no owner row, fires no owner write events and queues no reindex. Its external ids' `synced_at` is stamped, in one update for all records classified `unchanged` of the batch (`ExternalIds::touch()`), after the write loop. Skipped and failed records are never stamped.
6. A created owner gets its slug as `CreateOwnerAction` makes it. Slugs are claimed for the batch in record order, so two new owners with the same label get `x` and `x-2`. An updated owner keeps its slug.
7. When a record sets or changes an owner's flags, on create or update, the change must pass the `assign` ability, as in the single actions. A record whose change is refused fails, and its neighbours are still written.
8. Each created or updated owner fires the same owner action and model events as `CreateOwnerAction` or `UpdateOwnerAction`. The batch fires `OwnersUpsertingActionEvent` before and `OwnersUpsertedActionEvent` after, with the result. An owner's `*Updated` events fire, and the owner is recorded for reindex, only after that owner's transaction commits; a rolled-back owner fires neither.
9. When owners move or their flags change, the products beneath them get their index syncs through `ProductIndex::deferring()`: the batch lists the products under each moved or flag-changed owner and queues them together, one sync job per 500 products, not one per owner (D-008). The test uses `Queue::fake()` and asserts the jobs pushed and the product ids they carry.
10. A record that fails validation, a rule or the database is reported in `failures` with its index, code and errors. Every other record in the batch is still written. A database error on one owner rolls back that owner only. The test injects the fault with a model `saving` listener that throws for one code, and asserts that owner's row, external ids and flags are absent while its neighbours exist. A unique-index violation on `code` is reported as a `code` failure, not a raw database error.
11. **Parity.** The same owner records written through the batch and through `CreateOwnerAction` and `UpdateOwnerAction` leave the same stored rows: code, type, labels, parent, path, slug, external ids and flags. A dataset per scenario (create, update, move, flags, refused) compares an explicit column list that excludes ids and timestamps. The single actions fire the same events in the same order, with the same transaction boundary, as before the `OwnerWriter` refactor. The datasets include an update record with absent fields.
12. **Query bounds.**
    - **Unchanged:** a batch of unchanged records runs the same number of queries at 10 records and at 100.
    - **Written (D-007):** a batch runs at most 9 queries plus 10 per created owner and 13 per updated owner (the worst case: a move plus label, external-id and flag changes), against about 14 through the single actions. `UpsertOwnersPerformanceTest` pins these figures at 10 and 100 records.
12a. **Path safety.** After a record moves an owner, later records in the batch see the new `path` of the moved owner and its descendants, never the old one. Tests: two records that would swap two owners under each other (the second is refused as a move under a descendant), and a child created under a parent that moved earlier in the batch gets the parent's new path.
12b. **Size.** The action expects at most `showroom.impex.chunk` records per call and chunks its `whereIn` lookups; a caller with more records chunks them. The query-bound tests run at 10 and 100 records.
13. `showroom:upsert-owners` takes the owner records with the run: a list, or `{"owners": [...]}` as a webhook body. It writes them `showroom.impex.chunk` at a time, each chunk through one `UpsertOwnersAction` call, and accepts `mode` and `match`.
    - Its run result counts rows: `total`, `succeeded`, `failed` and `unchanged`. As for products, `succeeded` is `total - failed`, so unchanged and skipped rows count inside `succeeded` (D-006). A chunk test with a skipped row asserts it.
    - Each failed row's reason is kept on its chunk's batch item, by its global position in the records sent: the chunk-relative index from the action is offset by the chunk's start. A test with more than one chunk asserts this.
    - A payload that is not a list or `{"owners": [...]}` with an array fails the run before any chunk is written. A chunk that throws as a whole reports its rows as failed and the run continues with the next chunk.
    - Running the same records twice reports every row `unchanged` the second time.
    - The flow is registered with the Impex integration next to `showroom:upsert-products`.
14. Core code (outside `src/Impex`) does not reference Impex classes. The existing arch rule passes.

## Design

### Core (`src/Domains/Owner`)

The shared write logic lives in an `@internal` `Owner/Services/OwnerWriter` (D-001).

- **`Data/UpsertOwnersData`:** `records` (list), `mode` (`UpsertMode`), `match` (`code` or a source, validated with `ExternalIds::SOURCE_PATTERN`).
- **`Data/UpsertOwnersResult`:** `created`, `updated`, `unchanged`, `skipped` (a count) and `failures` (`{index, code, errors}`), shaped like `UpsertProductsResult` but keyed by `code` instead of `identifier`.
- **`Actions/UpsertOwnersAction`:**
  1. **Match.** Load the matched owners in one query, by code, or by `ExternalIds::find(OwnerModel, source, ids)` followed by one owner query. Load what the comparison needs with them: `type`, `parent`, `externalIds` and flags.
  2. **Load the batch's context once.** That is the owner types named, with `parentTypes`, and the parents named by code. Parents created by earlier records join an in-memory map as they are written.
  3. **Validate and preview each record.** Use the `CreateOwnerData` or `UpdateOwnerData` rules. Validation `exists` and `unique` lookups are primed for the batch through `BatchPresenceVerifier`, as products do. The preview compares the sent fields with the loaded owner in memory.
  4. **Stamp unchanged records** with one `ExternalIds::touch()`.
  5. **Write the rest in record order, each in its own transaction.** The write reuses the single actions' placement, slug, external-id and flags logic, given the preloaded type and parent. It does not reload the return graph.
  6. **Reindex once.** The reindex runs inside `ProductIndex::deferring()`, so the batch queues one sync.
- **Shared logic.** To avoid two copies of the placement and write rules, the logic in `CreateOwnerAction` and `UpdateOwnerAction` moves into an internal service (`Owner/Services/OwnerWriter`) that both the single actions and the batch call. The single actions keep their public behaviour and their existing tests.

### Impex (`src/Impex`)

- `Flows/UpsertOwnersFlow` (`showroom:upsert-owners`) and `Actions/UpsertOwnerChunk` mirror `UpsertProductsFlow` and `UpsertProductChunk`, with an inline owner source.
- The flow is registered in `ImpexIntegration` next to `UPSERT`.

### Events

New `OwnersUpsertingActionEvent` and `OwnersUpsertedActionEvent`, as for products.

## Out of scope

- Deleting, archiving or pruning owners that are absent from the batch.
- Managing owner types, and changing a stored owner's code or type (a record that sends a different one fails, per criterion 2).
- Sorting records parent-first, within a batch or across Impex chunks. The caller orders them.
- Whole-batch atomicity. Atomicity is per owner (criterion 10).
- HTTP and MCP surfaces for the batch.
- Writing new owners in a single bulk insert. Each new owner needs its id for its path, so new owners are written one at a time within the per-owner bound.
- Owner metadata (logos, descriptions).
- Switching the `secret` app's `SyncVendorsAction` and `SyncSeriesAction` to this action. That is a follow-up spec in `secret`.

## Risks and open questions

- **Refactoring the single actions into `OwnerWriter`** touches create and update paths that are already tested. Their existing tests must pass unchanged. `OwnerWriter` is `@internal`, not public API, and the actions stay `final` with unchanged constructor signatures.
- **In-batch parents** depend on record order. A caller must send umbrellas before their children; `secret`'s vendor sync already does this, roots first.
- **Query bound.** The per-owner 6 and the constant `C` are measured on the first working build and fixed in criterion 12 before the test is merged.
- **Authorization (D-004).** The action checks only the flags `assign` ability per record. Callers authorize create and update, as for `UpsertProductsAction` and the single owner actions. The Impex flow runs under the channel's own authorization.
- **Concurrency (D-005).** A change between the batch's preload and its write is not detected: last write wins, as for products.
