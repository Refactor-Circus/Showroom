# Tasks: Batch owner upsert

## Group 1: OwnerWriter extraction
depends on: none
files: src/Domains/Owner/Services/OwnerWriter.php, src/Domains/Owner/Actions/CreateOwnerAction.php, src/Domains/Owner/Actions/UpdateOwnerAction.php, tests/Feature/OwnerWriterTest.php
criteria: 4, 6, 7, 11

- [x] 1.1 Write OwnerWriterTest. Cover create and update through OwnerWriter with a preloaded type and parent: placement rules (can_be_root, parent_types, no move under the owner itself or a descendant, path of the owner and its descendants); slug claimed as CreateOwnerAction does; external ids and flags written; assign refused when flags are set or changed. Assert that the existing owner tests (SlugsTest, FlagAssignmentTest, ExternalIdsTest, RecordLookupTest, Ui/OwnerPagesTest) still pass unchanged.
- [x] 1.2 Extract the placement, slug, external-id and flags logic of CreateOwnerAction and UpdateOwnerAction into an @internal `Owner/Services/OwnerWriter` (D-001). It accepts a preloaded type and parent and does not reload the return graph. The single actions keep their final classes, constructor signatures, events, transaction boundary and return values.
- [x] 1.3 Run vendor/bin/pest; all green

## Group 2: UpsertOwnersAction
depends on: 1
files: src/Domains/Owner/Data/UpsertOwnersData.php, src/Domains/Owner/Data/UpsertOwnersResult.php, src/Domains/Owner/Actions/UpsertOwnersAction.php, src/Domains/Owner/Events/OwnersUpsertingActionEvent.php, src/Domains/Owner/Events/OwnersUpsertedActionEvent.php, tests/Feature/UpsertOwnersActionTest.php, tests/Feature/UpsertOwnersParityTest.php, tests/Feature/UpsertOwnersPerformanceTest.php, tests/Feature/EventsTest.php, docs/06-ownership.md, CHANGELOG.md
criteria: 1, 2, 2a, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 12a, 12b

- [x] 2.1 Write UpsertOwnersActionTest, UpsertOwnersParityTest and UpsertOwnersPerformanceTest, mirroring the UpsertProducts tests. Cover: input, modes and match by code or by an external-id source; validation with named error keys; code or type change refused; absent fields left alone and `parent: null` to the root (D-002); duplicates claimed by the first record whatever its outcome (D-003); in-batch parents in record order; placement rules; the unchanged skip with one `synced_at` touch; slugs in record order; `assign` on create and update; events, including OwnersUpserting and OwnersUpserted; one queued reindex per batch (Queue::fake); per-record failures and a database error isolated to its owner; parity datasets against the single actions, including an absent-field update; path safety (swap, moved parent); size limit and chunked lookups; query bounds (unchanged equal at 10 and 100; written at most C + 6 per owner, with C measured and fixed).
- [x] 2.2 Implement UpsertOwnersData, UpsertOwnersResult (created, updated, unchanged, skipped count, failures {index, code, errors}), the two action events, and UpsertOwnersAction. It loads matched owners, types with parent types, and parents once per batch; primes BatchPresenceVerifier; previews in memory; touches unchanged records; writes each record in its own transaction through OwnerWriter; and reindexes inside ProductIndex::deferring(). Callers authorize create and update; only `assign` is checked per record (D-004). Last write wins on a concurrent change (D-005).
- [x] 2.3 Add the new events to EventsTest's expectations, document the batch in docs/06-ownership.md, and add a CHANGELOG entry.
- [x] 2.4 Run vendor/bin/pest; all green

## Group 3: showroom:upsert-owners Impex flow
depends on: 2
files: src/Impex/Flows/UpsertOwnersFlow.php, src/Impex/Actions/UpsertOwnerChunk.php, src/Impex/OwnerChunkSummary.php, src/Domains/Transfer/Events/OwnerChunkUpsertingActionEvent.php, src/Domains/Transfer/Events/OwnerChunkUpsertedActionEvent.php, src/Impex/ImpexIntegration.php, tests/Feature/Impex/UpsertOwnersFlowTest.php, tests/ArchTest.php
criteria: 13, 14

- [ ] 3.1 Write UpsertOwnersFlowTest, mirroring tests/Feature/Impex/UpsertChunkTest.php and the upsert-products flow tests. Cover: records as a list or as {"owners": [...]}; one action call per showroom.impex.chunk; mode and match passed through; counts total, succeeded (= total - failed, skipped and unchanged inside it, D-006) and failed, plus unchanged; failures kept on the batch item at their global position over more than one chunk; a malformed payload fails before any chunk; a chunk that throws reports its rows failed and the run continues; a second run reports every row unchanged. Confirm that ArchTest's core-does-not-use-Impex rule passes.
- [ ] 3.2 Implement UpsertOwnersFlow (`showroom:upsert-owners`), UpsertOwnerChunk, OwnerChunkSummary and the chunk events, following UpsertProductsFlow, UpsertProductChunk and ProductChunkSummary. Register the flow in ImpexIntegration next to UPSERT.
- [ ] 3.3 Run vendor/bin/pest; all green
