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

## D-007: Criterion 12 caps a written owner at C + 6 queries; the build measures C = 9, 10 per created owner and 13 per updated owner. What should happen?
- Choice: Amend criterion 12 to the measured bound (C = 9, 10 per created owner, 13 per updated owner), already pinned in UpsertOwnersPerformanceTest
- Rationale: Accepted the recommendation. Written owners are rare after the first load, and the rest of the cost is in the shared Flags, ExternalIds and Slugs services.
- Date: 2026-11-10
- Raised by: build (group 2)

## D-008: Criterion 9 asked for one queued sync per batch; the batch queues the products under moved or flag-changed owners through ProductIndex::deferring(), one SyncProductIndex per 500 products. Accept?
- Choice: Accept the current shape, on the existing ProductIndex API
- Rationale: Accepted the recommendation.
- Date: 2026-11-10
- Raised by: build (group 2)
