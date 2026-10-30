# Decisions

Earlier D-ids are recorded in shape.md.

## D-020: Suite: proving the package runs without Atrium, and refusing an Atrium too old for the screen registry
- Choice: Prove it both ways: a WithoutAtrium suite inside `composer test`, and a CI lane with Atrium uninstalled. Every package that renders Atrium screens adds a composer `conflict` on refactor-circus/atrium versions older than the screen-extensions registry (Atrium PR #16).
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user, cross-spec decision (2026-10-29)

## D-021: Suite: malformed integration config entries (screen maps, slots) at boot
- Choice: Throw InvalidArgumentException in local and testing. In production, report once, skip the entry and keep booting (same rule as Atrium D-022).
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user, cross-spec decision (2026-10-29)

## D-022: With `showroom.authorization` off, `ScreenAccess::allows()` returns true for everything, so every `permission()` extension (including another package's ability) shows on Showroom screens. Which rule applies?
- Choice: Keep it: the access closure uses Showroom's own authorization and documents it in the README; registrants needing their own check use `authorize()`
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-023: Where does the `row.actions` slot render on `showroom.transfers`, given that Impex's run-link section will otherwise duplicate the flow name?
- Choice: A trailing cell like every other list; Impex's section renders 'Open run' instead of the flow name
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-024: Q1: What should `permission()` extensions do on Showroom screens when `showroom.authorization` is off?
- Choice: Keep ScreenAccess::allows() as is: every permission() extension shows, documented in the README; registrants needing their own check use authorize()
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-025: Q2: Where does the transfers `row.actions` slot render relative to the flow name?
- Choice: Trailing cell, as on every other list; Impex's section renders 'Open run'
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-026: Q1: What should a `permission()` extension do on Showroom screens when `showroom.authorization` is off?
- Choice: Keep `ScreenAccess::allows()` as is: every `permission()` section shows when authorization is off; document it, and registrants who need their own check use `authorize()`
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-027: Q2: Where does `row.actions` render on the `showroom.transfers` list?
- Choice: A trailing cell, the same as every other list; Impex's section can render 'Open run'
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)
