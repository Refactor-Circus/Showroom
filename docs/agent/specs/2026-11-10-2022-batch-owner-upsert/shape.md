# Batch owner upsert — Shaping Notes

## Scope

`UpsertOwnersAction`: a core action that creates and updates a batch of owners from records in API shape. It is the owner counterpart of `UpsertProductsAction`, and ships with an Impex flow, `showroom:upsert-owners`, like `showroom:upsert-products`.

### Why

The `secret` app's catalog import (`core:vendors`, `core:series`) syncs about 3,100 vendor and series owners through `CreateOwnerAction` and `UpdateOwnerAction`, one owner at a time.

- **About 14 app-database queries per owner.** Validation checks that the type and parent exist and the code is free. The type is loaded with its parent types, the parent is loaded by code, and a slug is claimed. The owner is saved and placed (its path). External ids and flags are written, then the type, parent, external ids and flags are reloaded for the return value.
- **The owner type alone is read 3–4 times per owner.**
- **Measured:** 1,744 selects and 477 writes for a page of 200 vendors.

### What the action does

- **Input:** `UpsertOwnersData` holds:
  - `records`: owner records in API shape (`code`, `type`, `labels`, `parent` by code, `external_ids`, `flags`);
  - `mode`: upsert, create or update, as for products;
  - `match`: `code`, or an external-id source whose ids match records to owners.
- **Loads once per batch:**
  - the owner types used, with their parent types;
  - the parents named, including owners created earlier in the same batch, so an umbrella and its children can go in one call when the umbrella comes first;
  - the matched owners, with their external ids and flags.
- **Unchanged skip:** a matched record whose sent fields equal the stored owner's writes nothing. Its external ids are stamped synced in one update for the batch (`ExternalIds::touch()`), as for products.
- **Same rules as the single actions:**
  - `CreateOwnerData`'s and `UpdateOwnerData`'s validation;
  - placement (`can_be_root`, `parent_types`, no cycles, path);
  - generated slugs, claimed for the batch (`Slugs::forMany`);
  - the flags `assign` ability when flags change;
  - per-record authorization as create or update would do it.
- **Same side effects:**
  - action and model events as create and update fire them;
  - products under an owner that moved or whose flags changed are reindexed, once per batch.
- **Failures:** a record that fails validation, a rule, or the database is reported with its position and reason in `UpsertOwnersResult` (created, updated, unchanged, skipped, failures), and the rest of the batch is still written.
- **Query count:** it must not grow with the number of records for unchanged pages. A test asserts a fixed bound, and another bounds new records to a small constant per record.

### `showroom:upsert-owners` (Impex)

- Owner records passed in with the run: a list, or `{"owners": [...]}` as a webhook body.
- Written `showroom.impex.chunk` at a time, each chunk by one call to `UpsertOwnersAction`.
- The result counts rows (`total`, `succeeded`, `failed`, `unchanged`), with each failed row's reason kept on its chunk's batch item.
- Same structure as `UpsertProductsFlow` and `UpsertProductChunk`.

### Out of scope

- HTTP and MCP surfaces for the batch.
- Owner metadata (logos, descriptions).
- Bulk writing of new owners in a single insert. New owners may still be written one at a time, since they need ids for their paths, as long as the per-record query count stays a small constant.
- Switching the `secret` app's `SyncVendorsAction` and `SyncSeriesAction` to the new action. That is a follow-up spec in `secret`, once this one is released.

## Decisions

- **Action plus Impex flow**, mirroring products.
- **Match by code or an external-id source**, like products' identifier or source.
- **Parents within the batch** are resolved in record order. A child that comes before its parent fails as if the parent did not exist.
- **The single actions stay.** `CreateOwnerAction` and `UpdateOwnerAction` keep their behaviour and API.

## Context

### Product alignment

- Mission: "Dynamic hierarchical ownership", and "Full HTTP API with MCP parity". The batch is action- and flow-only, as for products: there are no HTTP or MCP surfaces for batches yet.
- Roadmap item 7: "Import / export via Impex": bulk loads from ERPs.

### References

See `references.md`.

### Visuals

None; there is no UI.

## Standards

- `actions/actions-events`: the new action and its starting and finished events, and the events the single actions fire for each record.
- `data/data-objects`: `UpsertOwnersData` and `UpsertOwnersResult`.
- `http/authorization`: each record authorized as create or update; flags `assign`.
- `architecture/dependencies`: the action is in core, and the flow is in the optional Impex integration.
- `models/models`: owners, owner types and external-id lookups loaded in batch.
- `models/stored-identifiers`: matching by code or by an external-id source.
- `testing/test-layers`: behaviour and query bounds tested at the action; the flow tests its wiring.
- `testing/arch-tests`: new classes pass the arch preset.
