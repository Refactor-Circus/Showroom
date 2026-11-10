# Spec review

## Round 1

Revised spec.md for the critic findings. Open questions (route fallback, Flags in or out of this spec, Atrium screens, identifier/external-id case and accent handling, who may assign flags, empty or reserved slugs, the query-count bound for AC 23/25, where to test webhook suppression, bulk-vs-action parity as an AC) were left untouched; the goal, ACs 7, 22-25 bounds and Atrium scope are unchanged on those points.

Changed:
- AC 2: boundary dataset, stricter-pattern test, fail-fast on a non-compiling pattern; Design passes the pattern as an array rule (pipe-safe).
- AC 3, 4: concrete slug examples (collision, 191-char truncation, no gaps, `a`,`a`,`a-2`); Design: existing and in-batch slugs count as taken.
- AC 5: read-only keys (`slug`, `effective_flags`) refused, with a round-trip test; upgrade note added.
- AC 6 and migration: idempotent `slug IS NULL` backfill, NOT NULL/unique only at zero NULLs, lock duration documented, chunk and re-run tests.
- AC 7, 8, 11: route dataset from `route:list`; named export/MCP fields; empty external_ids serialises as `{}`.
- AC 10, 12, 18: in-batch duplicate external id, atomic failure, Scout filter values quoted and escaped (injection test), shared two-engine dataset for flags.
- AC 19: split into queue-fake and search checks, with grandchild owner; Design states the known cost, single `QueueProductSyncs` job and `changed_at` behaviour.
- AC 22: failure isolation in mixed chunks; Design falls back to per-record on a bulk chunk write failure.
- AC 23: named observables and per-category changed-field test.
- AC 27: CLI option names, global source row index for failures.
- AC 28: tied to AC 26 with the exact arch expectation.
- New AC 29 (flag assignments deleted with subject, orphans ignored by flag delete), AC 30 (Scout settings), AC 31 (`suppress()` restores state in `finally`).
- Impex change and route change recorded as upgrade notes; Out of scope gained slug redirects/editing, file-format fields, webhook payload changes.

Why: closes gaps that are not architectural decisions (data integrity, idempotency, testability, injection, observability) while leaving the user-facing design questions open.

### Round 1, revision for recheck findings

Left alone: the open question on HTTP lookup of identifiers containing `/` and spaces. AC 1 now has a lookup test that holds whichever way it is resolved, and Risks keeps the question open.

Changed (ACs renumbered; AC 1-6 keep their numbers, old 7-31 are now 8-35 with new ACs inserted and 36-41 added):
- D-001: AC 8 now asserts ULID `id` binding by default (route:list dataset incl. Workflow and Atrium). New AC 9 (configured lookup keys), AC 10 (`?by=` and MCP `by`, invalid `by` gives 422), AC 11 (boot-time failure on invalid config). New Design > Record lookup (single `RecordLookup` resolver, `LookupKey` enum, binders reading `?by=`, which actions accept `by`, flags keep `code` binding). Config lists both lookup keys. Routes bullet and Atrium links follow the configured key. Upgrade notes say clients break unless the host sets identifier/code. Open Risk bullet on the fallback replaced by the remaining slash question. Why: body and ACs contradicted the settled decision.
- D-006: new AC 7 (empty base `product`/`owner`, owner order label, code, `owner`, `Slugs::RESERVED` derived from route segments, 191-char case); AC 4 and 6 and the Slugs design use the same rules, backfill tested over empty-slug rows.
- D-005: new AC 36 (one normalization, case and accent folding; collision, upsert match, in-batch duplicate and Scout filter cases; tests do not depend on DB collation).
- D-004: new AC 37 (`assign` on create, update and upsert, per-record check before preview and bulk path, refusal at `flags`, tests for HTTP/MCP/Atrium/mixed chunk, owners too). AC 19, FlagPolicy design and Batch upsert flow and Authorization updated.
- D-003/D-002: Goal, AC 40 and 41, new Design > Atrium (registered in `Atrium/ShowroomPlugin.php`).
- D-007: AC 27 and 29 run two batch sizes (10 and 100) against one fixed constant; AC 29 also asserts count at 100 is below 100.
- D-008: new AC 38 (bulk vs `CreateProductAction` parity incl. slug) and AC 39 (mixed chunk and forced bulk failure fallback).
- D-009: AC 30 now requires the Impex-layer `CaptureProductChanges` test and cross-references AC 35 (core) and AC 32 (arch rule).
- Addendum replaced by a short change log mapping D-001 to D-009 to the body. Cross-references (AC 30, 34) updated for the renumbering.
