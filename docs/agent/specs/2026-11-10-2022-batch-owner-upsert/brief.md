Risk: medium

## What changed
- Owner domain: new `UpsertOwnersAction` with `UpsertOwnersData`/`UpsertOwnersResult` and `OwnersUpsertingActionEvent`/`OwnersUpsertedActionEvent`. It matches, validates, previews and writes a batch of owners, each in its own transaction, and stamps unchanged ones with one `ExternalIds::touch()`.
- Refactor: the placement, slug, external-id and flags logic of `CreateOwnerAction` and `UpdateOwnerAction` moved into an `@internal` `Owner/Services/OwnerWriter`, shared with the batch. Constructors and public behaviour stay the same.
- Impex: `showroom:upsert-owners` flow, `UpsertOwnerChunk`, `InlineOwnerSource`, `OwnerChunkSummary` and chunk events, registered in `ImpexIntegration`.
- Docs: `docs/06-ownership.md` and `CHANGELOG.md`. No composer.json, config, migration or contract changes.

## Why
Host apps that sync owners need a batch path whose cost does not grow per unchanged record, instead of about 14 queries per owner through the single actions. Decisions that shaped it: D-001 shared `OwnerWriter`; D-002 absent field keeps the stored value and `parent: null` means root; D-003 the first record claims a code or external id and later duplicates fail; D-004 callers authorize, the action checks only the flags `assign` ability; D-005 last write wins; D-006 `skipped` is a count and counts as succeeded in Impex; D-007 the written bound is 9 queries plus 10 per created and 13 per updated owner; D-008 products under moved owners are queued through `ProductIndex::deferring()`, 500 per job.

## Look at
- `src/Domains/Owner/Services/OwnerWriter.php:1`: the extracted logic now behind both single actions. Check that event order and transaction boundaries are unchanged.
- `src/Domains/Owner/Actions/UpsertOwnersAction.php:177`: the `writeBatch` flow, ordering of unchanged stamping, write loop and deferred reindex.
- `src/Domains/Owner/Actions/UpsertOwnersAction.php:609`: `carryBeneath`, which keeps in-batch paths of moved owners and descendants current (criterion 12a).
- `src/Domains/Owner/Actions/UpsertOwnersAction.php:390`: `claim`, the duplicate rule (D-003).
- `src/Impex/Actions/UpsertOwnerChunk.php:1`: chunk failure offsets by global row position and whole-chunk failure handling.

## Tested
- `tests/Feature/UpsertOwnersActionTest.php`: criteria 1 to 10, 12a and 12b (modes, validation, duplicates, in-batch parents, placement, unchanged, slugs, assign, events, index, DB errors, path safety, size).
- `tests/Feature/UpsertOwnersParityTest.php` and `tests/Feature/OwnerWriterTest.php`: criterion 11 (same rows and events as the single actions).
- `tests/Feature/UpsertOwnersPerformanceTest.php`: criterion 12 query bounds at 10 and 100 records.
- `tests/Feature/Impex/UpsertOwnersFlowTest.php`: criterion 13. `tests/Feature/EventsTest.php` and the arch rule: criteria 8 and 14.
- Gate round 1 passed: phpstan, pint, 1370 Pest tests, arch/coverage, ringside check.

## Risks
- The refactor touches the tested create and update paths; the existing tests pass, but it is the main regression surface.
- Records must be ordered parent-first by the caller, within a batch and across chunks.
- Concurrent changes between preload and write are not detected (D-005).
- The written-owner query cost is still 10 to 13 per owner (D-007); the saving is mostly on unchanged records.
- The diff is large (about 3,600 lines, mostly tests and `UpsertOwnersAction`).
- Open items: none (nits: 0).
