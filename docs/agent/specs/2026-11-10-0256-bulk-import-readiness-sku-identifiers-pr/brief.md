Risk: high

## What changed
- Identifiers: product identifiers may now hold spaces and `/`, governed by `showroom.products.identifier_pattern` (fails fast if it does not compile).
- Slugs: products and owners get a unique, read-only `slug` from a shared `Support\Slugs` generator. A chunked, resumable migration backfills existing rows and then makes the column non-null and unique.
- Lookup: products and owners bind by ULID `id` by default. `showroom.lookup.products` and `showroom.lookup.owners` switch the key to `slug`, and a per-call `by` overrides it, via `Support\RecordLookup` across HTTP routes, MCP tools, actions and Atrium.
- External IDs: a new `Domains/ExternalId` domain and `showroom_external_ids` table, shaped like Roster's links table: `source` (64), `external_id` (191), nullable `synced_at`, `unique(subject_type, source, external_id)` and `unique(subject_type, subject_id, source)`. Products and owners take an `external_ids` map and return it with a read-only `external_ids_synced_at` map. Listings filter by `external[source]=id`. `ExternalIds::touch()` stamps `synced_at` for unchanged upserts, as Roster's `SyncCustomersAction::touch()` does.
- Flags: a new `Domains/Flag` domain (CRUD actions, HTTP routes, MCP tools, `FlagPolicy` with `assign`). Flags are assigned to products and owners, inherited down the owner tree, and returned as `effective_flags`. They are searchable on the database and Scout engines.
- Owner types: an owner type may list its own code in `parent_types`.
- Batch upsert: a new `UpsertProductsAction` with a bulk-insert path (`BulkProductWriter`) and an unchanged preview. Impex now processes whole chunks (`UpsertProductChunk`), and `ImportProductRow` is removed.
- Atrium: a flag-definitions screen, plus slug, external ids and flags on the product and owner screens.
- Key normalization: `KeyNormalizer` gives case- and accent-insensitive matching everywhere.

## Why
ERP-scale catalogs (millions of simple products under a vendor, series, owner tree) must load and resync cheaply. Real SKUs need to be valid identifiers. URLs need stable slugs. Records need matching by external-source ids. Commercial flags need to inherit down the owner tree. Unchanged records must cost almost nothing on re-sync.

Decisions that shaped it:
- D-001 and D-010: lookup is by `id` or `slug` only, with no identifier or code fallback, so SKUs with `/` never reach a URL.
- D-004: flag assignment needs a separate `assign` ability.
- D-005: the database collation is kept and all in-memory matching is keyed the same way.
- D-006: empty or reserved slug sources fall back to `product` or `owner` and are suffixed.
- D-007 and D-008: query-count bounds are chosen by the implementer, and bulk-path versus action-path parity is an acceptance criterion.
- D-009: webhook suppression is tested at both the core and Impex layers.
- D-011: capture is turned off on the CLI with `--capture-changes=0`.
- D-012: the Transfer domain and `TransferUiController` are named exceptions to the arch rule.
- D-013: external ids follow Roster's links table (`source`, `synced_at`, one id per record and source).
- D-002 and D-003: flags and the Atrium screens stay in this spec.

## Look at
- `src/Domains/Product/Actions/UpsertProductsAction.php:119`: the batch flow (match, validate with the `assign` check, unchanged preview, write). Check that failure isolation and `capture_changes` suppression behave.
- `src/Domains/Product/Services/BulkProductWriter.php:1`: the bulk insert duplicates part of `CreateProductAction`. This is the drift risk, and the per-record fallback is in `UpsertProductsAction.php:450`.
- `database/migrations/2026_11_10_000001_add_slugs_to_showroom_products_and_owners.php:1`: the chunked backfill and the switch to non-null and unique. It runs on production-size tables and will hold a long lock on MySQL.
- `src/Support/RecordLookup.php:44`: the lookup resolver behind every route binder, MCP tool and action. A breaking URL change lives here.
- `src/Domains/ExternalId/Services/ExternalIds.php:215`: `touch()` and the `synced_at` stamping, plus the two unique constraints in `database/migrations/2026_11_10_000002_create_showroom_external_ids_table.php:30`.

## Tested
Round 2 gate: PASS. `composer test` ran phpstan with 0 errors, pint clean, Pest 1273/1273 passed (second suite 6/6), coverage 100%, and `ringside check` passed. Every acceptance criterion is mapped to tests in `round-2/gate.md`. The main mappings:
- AC 1-2 identifiers: `tests/Feature/Api/ProductIdentifierTest.php`
- AC 3-7 slugs and backfill: `tests/Feature/SlugsTest.php`, `tests/Feature/SlugBackfillTest.php`, `tests/Unit/SlugsTest.php`
- AC 8-11 lookup: `tests/Feature/RecordLookupTest.php`
- AC 13-16, 36, 42-44 external ids, `synced_at` and key normalization: `tests/Feature/ExternalIdsTest.php`, `tests/Unit/KeyNormalizerTest.php`, `tests/Feature/UpsertProductsActionTest.php` (the gate maps 42-44 by file, not by test name)
- AC 17-23, 33, 34, 37 flags: `tests/Feature/FlagActionsTest.php`, `tests/Feature/FlagAssignmentTest.php`, `tests/Feature/Search/FlagSearchTest.php`
- AC 24 owner types: `tests/Feature/Api/OwnerTypeSelfParentTest.php`
- AC 25-30, 38, 39 upsert, parity and performance: `tests/Feature/UpsertProductsActionTest.php`, `tests/Feature/UpsertProductsParityTest.php`, `tests/Feature/UpsertProductsPerformanceTest.php`
- AC 30-31, 35 Impex and webhook suppression: `tests/Feature/Impex/UpsertChunkTest.php`, `tests/Feature/Impex/ProductWebhooksTest.php`, `tests/Unit/ProductChangeCaptureTest.php`
- AC 32 arch rule: `tests/ArchTest.php`
- AC 40-41 Atrium: `tests/Feature/Ui/FlagPagesTest.php`, `tests/Feature/Ui/ProductPagesTest.php`, `tests/Feature/Ui/OwnerPagesTest.php`

## Risks
- Breaking for consumers: product and owner URLs built from the identifier or code stop working, because routes bind by `id` with no fallback. Clients that echo a full resource back on update must strip `slug`, `effective_flags` and `external_ids_synced_at`. `ImportProductRow` and `ProductRowImportedActionEvent` are removed, and run results gain `unchanged`. All of this is in the changelog and upgrade notes.
- Schema: three new migrations. The slug unique index on a very large MySQL table holds a long lock.
- Config: new keys `products.identifier_pattern`, `lookup.*`, `tables.external_ids`, `tables.flags`, `tables.flag_assignments` and `policies.flag`. An invalid lookup value throws at boot.
- A flag change on a top-level owner queues index syncs for every product beneath it (about 2M for the largest vendor). It is one queued job, but it bumps `changed_at` for feed and webhook consumers.
- Drift between the bulk-insert path and the actions. It is mitigated by the parity tests (AC 38, 39).
- Standards deviation: referencing records by `id` is recorded in `docs/agent/standards-feedback.md`, citing D-001.
- Open findings: none (nits: 1). The one nit is that the gate maps AC 42-44 by file, not by named test. Broken packages: none identified.
