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
