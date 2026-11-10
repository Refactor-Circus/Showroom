# Batch owner upsert

## Goal

Host apps that sync owners from another system (the `secret` app's catalog import, later ERP syncs) can create and update a batch of owners in one call. The cost must not grow per unchanged record, and must stay a small constant per written one, instead of about 14 queries per owner through `CreateOwnerAction` and `UpdateOwnerAction`.

## Acceptance criteria

### Action

1. `UpsertOwnersAction::execute(UpsertOwnersData)` takes:
   - `records`: owner records in API shape (`code`, `type`, `labels`, `parent` as an owner code, `external_ids`, `flags`);
   - `mode`: `upsert` (default), `create` or `update`, as `UpsertMode` does for products;
   - `match`: `code` (default), or an external-id source whose ids match records to stored owners.

   It returns `UpsertOwnersResult` with `created`, `updated`, `unchanged`, `skipped` and `failures` (each `{index, code, errors}`, in batch order). In `create` mode a matched record is skipped; in `update` mode an unmatched one is.
2. A record that is not an object fails with `record`. Every other record is validated with `CreateOwnerData`'s rules when it creates, and `UpdateOwnerData`'s when it updates. A matched record that sends a different `code` or `type` from the stored owner's fails, because those never change.
3. A record's `parent` may name an owner stored before the batch, or one created by an earlier record in the same batch. A record whose parent comes later in the batch fails as if the parent did not exist.
4. Placement follows the single actions:
   - a root must have a type with `can_be_root`;
   - a parent's type must be in the type's `parent_types`;
   - a move under the owner itself or one of its descendants is refused.

   A record that breaks a rule fails, and the owner is not written.
5. A matched record whose sent fields equal the stored owner's (labels, parent, external ids, flags) is `unchanged`. It writes no owner row, fires no owner write events and queues no reindex. Its external ids' `synced_at` is stamped, in one update for all unchanged records of the batch (`ExternalIds::touch()`).
6. A created owner gets its slug as `CreateOwnerAction` makes it. Slugs are claimed for the batch in record order, so two new owners with the same label get `x` and `x-2`. An updated owner keeps its slug.
7. When a record changes an owner's flags, the change must pass the `assign` ability, as in the single actions. A record whose change is refused fails, and its neighbours are still written.
8. Each created or updated owner fires the same owner action and model events as `CreateOwnerAction` or `UpdateOwnerAction`. The batch fires `OwnersUpsertingActionEvent` before and `OwnersUpsertedActionEvent` after, with the result.
9. When owners move or their flags change, the products beneath them get one queued index sync for the whole batch, not one per owner.
10. A record that fails validation, a rule or the database is reported in `failures` with its index, code and errors. Every other record in the batch is still written. A database error on one owner rolls back that owner only.
11. **Parity.** The same owner records written through the batch and through `CreateOwnerAction` and `UpdateOwnerAction` leave the same stored rows: code, type, labels, parent, path, slug, external ids and flags. One test covers both, including a mixed valid, invalid and refused batch.
12. **Query bounds.**
    - **Unchanged:** a batch of unchanged records runs the same number of queries at 10 records and at 100.
    - **Written:** a batch of new or updated owners runs at most a fixed constant plus 6 queries per written owner, against about 14 today. The test runs batches of 10 and 100 and asserts the bound at both.

### Impex

13. `showroom:upsert-owners` takes the owner records with the run: a list, or `{"owners": [...]}` as a webhook body. It writes them `showroom.impex.chunk` at a time, each chunk through one `UpsertOwnersAction` call, and accepts `mode` and `match`.
    - Its run result counts rows: `total`, `succeeded`, `failed` and `unchanged`.
    - Each failed row's reason is kept on its chunk's batch item, by its position in the records sent.
    - The flow is registered with the Impex integration next to `showroom:upsert-products`.
14. Core code (outside `src/Impex`) does not reference Impex classes. The existing arch rule passes.

## Design

### Core (`src/Domains/Owner`)

- **`Data/UpsertOwnersData`:** `records` (list), `mode` (`UpsertMode`), `match` (`code` or a source, validated with `ExternalIds::SOURCE_PATTERN`).
- **`Data/UpsertOwnersResult`:** shaped like `UpsertProductsResult`, with failures keyed by `code` instead of `identifier`.
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

- HTTP and MCP surfaces for the batch.
- Writing new owners in a single bulk insert. Each new owner needs its id for its path, so new owners are written one at a time within the per-owner bound.
- Owner metadata (logos, descriptions).
- Switching the `secret` app's `SyncVendorsAction` and `SyncSeriesAction` to this action. That is a follow-up spec in `secret`.

## Risks and open questions

- **Refactoring the single actions into `OwnerWriter`** touches create and update paths that are already tested. Their existing tests must pass unchanged.
- **In-batch parents** depend on record order. A caller must send umbrellas before their children; `secret`'s vendor sync already does this, roots first.
- **The bound of 6 queries per written owner** is a target. If placement, slug, external ids and flags cannot fit, review will ask for the number instead of loosening it silently.
