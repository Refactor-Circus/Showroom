# Spec review

## Round 1

Revised `spec.md` for all findings; the open questions (Q1 `permission()` with authorization off, Q2 transfers `row.actions` placement) are untouched and still open.

- Out of scope: added bullets for the unchanged JSON history route/MCP tool, no new screens/routes/config, `src/Impex` and Cortex wiring unchanged, and no Showroom-side History (Keen owns it).
- Criterion 1: added the declared-slot table (`Screens::SLOTS`; `history` not declared) and asserted the default `overview` tab, unknown/hidden `?tab=` behaviour and throwing-section behaviour, so the dataset can be written.
- Criterion 2: `Screens` passes the record to `ScreenAccess::allows()` only when `showroom.policies` covers its class, otherwise `$subject` (fixes the `RunModel` case); added a transfers `permission('view')` test and a subject-mapping dataset over all 25 screens.
- Criterion 3: now a dataset over all 25 screens including a guest (401).
- Criterion 4: layout stated as markup-order assertions over all list/detail screens, plus checks for the extra `th`, assets tile bottom and column class.
- Criterion 5: "no extra markup" defined as testid absence plus a stored baseline over 25 screens; added the same-query-count requirement, lazy row resolution and a README note that row closures must not query.
- Criterion 6: named Keen spec `history-section-by-capability`; the `tests/Keen` suite keeps JSON history tests and one real-History-section test on `showroom.product`.
- Criterion 8: explicit A/B reset test pair; parallel/random-order kept as a CI note.
- Criterion 9: added UPGRADE/changelog content and `conflict`/minimum-version constraints.
- Criterion 11: named the MCP tool and auth mode, added `tools/list`/boot test and transfer tool/route guarding on `ImpexIntegration::active()`, skip placement before boot, a lane-not-vacuous test, and Keen removed in the lane.
- Criterion 12: dropped line numbers, added a README doc-promise test, and scoped the grep to exclude `docs/agent/specs`.
- Not changed: the finding to record Q1/Q2 as decisions was not applied, because the open questions are to be left alone until the user answers.
