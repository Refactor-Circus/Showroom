# Decisions

## D-001: Should the old identifier/code route keys keep working for a release after routes bind by slug?
- Choice: Neither. Every lookup of products and owners uses the ULID `id` by default, for HTTP routes, MCP tools and the show/update/delete/workflow actions. The lookup key is configurable (`id`, slug, or identifier for products / code for owners) and can be overridden on each action call.
- Rationale: "i want everythgin to use the id (ulid) by default for lookup, even the owners. make it configurable and overridable on the action". Current MCP and API endpoints keep working through the configurable key.
- Date: 2026-11-10
- Raised by: spec review (scope, risk)

## D-002: Should the Flags domain ship in this spec or be split into its own spec?
- Choice: Keep flags in this spec
- Rationale: Accepted the recommendation: the batch upsert and search document already depend on flags.
- Date: 2026-11-10
- Raised by: spec review (scope)

## D-003: Do Atrium screens for flag definitions and for slug/external ids/flags on product and owner screens ship in this spec?
- Choice: Ship them in this spec
- Rationale: Accepted the recommendation.
- Date: 2026-11-10
- Raised by: spec review (scope)

## D-004: Who may assign flags to a product or owner?
- Choice: A separate `assign` ability on `FlagPolicy`, checked whenever `flags` changes
- Rationale: Accepted the recommendation: flags such as `restricted` carry commercial restrictions down whole owner trees.
- Date: 2026-11-10
- Raised by: spec review (risk)

## D-005: How should identifier and external-id uniqueness handle case and accents given the DB collation?
- Choice: Keep the database default collation (case-insensitive) and key all in-memory matching the same way
- Rationale: Accepted the recommendation.
- Date: 2026-11-10
- Raised by: spec review (risk)

## D-006: What should the slug of a record be when its source slugifies to an empty string, or to a reserved route segment?
- Choice: Fall back to a fixed base word (`product` / `owner`) and suffix, and suffix any reserved word
- Rationale: Accepted the recommendation: the backfill and bulk loads must never fail on a slug.
- Date: 2026-11-10
- Raised by: spec review (risk)

## D-007: How should the query-count bound for the batch upsert (AC 23, 25) be fixed so that a test can fail?
- Choice: Leave to the implementer to choose in the test
- Rationale: "Implementer decides". The test must still assert a concrete bound that fails on per-record query growth.
- Date: 2026-11-10
- Raised by: spec review (testability)

## D-008: Should bulk-path versus action-path parity be an acceptance criterion rather than only a risk mitigation?
- Choice: Yes, add an AC and a parity test comparing rows from both paths, with a mixed valid/invalid/guard-refused chunk for failure isolation
- Rationale: Accepted the recommendation.
- Date: 2026-11-10
- Raised by: spec review (testability)

## D-009: Where should the webhook-suppression behaviour (AC 26) be tested, given CaptureProductChanges is in the optional src/Impex layer and ProductChangeCapture is core?
- Choice: Core unit test for ProductChangeCapture::suppress state handling (including exception restore), plus an Impex-layer test asserting CaptureProductChanges records nothing under suppression and records by default
- Rationale: Accepted the recommendation.
- Date: 2026-11-10
- Raised by: spec review (testability)

## D-010: When a host sets the product lookup to `identifier`, how should HTTP routes handle SKUs containing `/`?
- Choice: Identifier (and owner code) are not lookup keys at all; lookup is only by ULID `id` (default) or `slug`, for products and owners
- Rationale: "identifier shouldnt be used! just ulid or slug"
- Date: 2026-11-10
- Raised by: spec review (recheck)

## D-011: How should turning off webhook capture be offered on the CLI for the Impex flows?
- Choice: Keep Impex as is; declare `?bool $captureChanges = null` (null means true), giving `--capture-changes=0`, and reword AC 31
- Rationale: Accepted the recommendation; stays inside Showroom.
- Date: 2026-11-10
- Raised by: build (group 9)

## D-012: The AC 32 arch rule fails on the existing Transfer domain and TransferUiController. Keep them as named exceptions?
- Choice: Keep the rule with the Transfer domain and TransferUiController as named exceptions
- Rationale: Accepted after clarifying that "core" means Showroom code outside src/Impex (Impex is optional), not the legacy core app.
- Date: 2026-11-10
- Raised by: build (group 9)
