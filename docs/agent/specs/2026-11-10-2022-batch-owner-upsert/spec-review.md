
## Round 1

Changed in spec.md:
- Goal: one sentence on the Impex flow for ERP and webhook bulk loads (scope finding).
- Criterion 2/3/4: named error keys per failure (`record`, `code`, `type`, `parent`, `flags`), so tests can assert them.
- Criterion 5: `synced_at` touch limited to records classified `unchanged`, run after the write loop.
- Criterion 8/9: `*Updated` events and reindex recording only after the owner's commit; `Queue::fake` assertion named; mid-batch throw keeps queued owners via `ProductIndex::deferring()` finally.
- Criterion 10: fault injection named (throwing `saving` listener); unique-index violation reported as a `code` failure.
- Criterion 11: dataset per scenario, explicit column list without ids/timestamps; single actions keep events, order and transaction boundary.
- Criterion 12: `C` measured on the first build and fixed before the test merges; counting via `DB::listen`, queue/cache excluded; "target" wording removed from Risks.
- New 12a (moves refresh paths of moved owners and descendants; swap and moved-parent tests) and 12b (batch size, chunked lookups).
- Criterion 13: chunk-relative indexes offset to global positions with a multi-chunk test; malformed payload, whole-chunk exception and re-run idempotency defined.
- Out of scope: pruning, owner type management, parent-first sorting, whole-batch atomicity.
- Risks: `OwnerWriter` `@internal`, actions stay `final` with same constructors.

Left alone because the open questions depend on them: absent-field semantics on update, duplicate codes / matched owners within a batch, per-record create/update authorization, concurrency between preload and write, and `skipped` reporting and counting (including the exact `total` identity). Findings that required deciding these were not applied and stay listed under Risks.

## Pass 1: recheck of D-001 to D-006

All six decisions landed. Fixed the four gaps the recheck found:
- 2a: the first record to use a code or matched external id claims it, whatever its outcome; later ones fail with `code`, write nothing, and never become in-batch parents. Tests for each case.
- 5 and 11: absent fields are left out of the unchanged comparison, and `parent: null` compares as the root. Tests for both, plus an absent-field parity dataset.
- 13 and Design: `succeeded = total - failed`, with unchanged and skipped rows counted inside it, as `ProductChunkSummary` does. `skipped` is listed as a result field.
- 7: `assign` applies when flags are set or changed, on create or update.
