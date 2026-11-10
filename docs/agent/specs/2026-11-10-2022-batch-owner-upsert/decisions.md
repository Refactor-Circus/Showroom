# Decisions

## D-001: How should the placement, slug, external-id and flags logic be shared between the single owner actions and the batch?
- Choice: Extract an internal `Owner/Services/OwnerWriter`, called by CreateOwnerAction, UpdateOwnerAction and the batch
- Rationale: Accepted the recommendation.
- Date: 2026-11-10
- Raised by: spec review (risk)

## D-002: On an update, what does a field absent from the record mean?
- Choice: An absent field leaves the stored value alone; an explicit `parent: null` moves the owner to root
- Rationale: Accepted the recommendation (UpdateOwnerData's `sometimes` rules).
- Date: 2026-11-10
- Raised by: spec review (testability)

## D-003: Two records for the same owner in one batch (same code, or same matched external id)?
- Choice: The later record fails as a duplicate; the first is written, as products claim keys in batch order
- Rationale: Accepted the recommendation.
- Date: 2026-11-10
- Raised by: spec review (scope, risk, testability)

## D-004: Should the batch enforce the owner create/update policy per record?
- Choice: No. Callers authorize; the action checks only the flags `assign` ability, as UpsertProductsAction and the single owner actions do
- Rationale: Accepted the recommendation.
- Date: 2026-11-10
- Raised by: spec review (scope, security)

## D-005: A concurrent change between the batch's preload and its write?
- Choice: Accept the race: last write wins, as UpsertProductsAction does
- Rationale: Accepted the recommendation.
- Date: 2026-11-10
- Raised by: spec review (risk)

## D-006: How are skipped records reported?
- Choice: A `skipped` count in UpsertOwnersResult; the Impex flow counts them as succeeded, as for products
- Rationale: Accepted the recommendation.
- Date: 2026-11-10
- Raised by: spec review (testability)
