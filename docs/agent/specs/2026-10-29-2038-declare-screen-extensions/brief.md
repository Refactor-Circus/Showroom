Risk: high

## What changed
- Views: all 25 Showroom Atrium screens (23 controller view files plus the transfers list) build a `Screen` through the new `Atrium\Support\Screens`, resolve it with `ScreenExtensions::resolve()` after their own authorization, and lay out `header.actions`, tabs, `before`, body, `aside`, `after` and per-row `row.actions`. Audit trails and the Impex run link are gone from the views, and the History nav item is removed from `ShowroomPlugin`.
- Controllers: `AuthorizesScreens` concern (401 for guests, 403 for signed-in users); each controller passes `resolved` to its view.
- Packaging: `refactor-circus/atrium` moves from `require` to `require-dev` and `suggest`, with a `conflict` on `<0.1.0`. Transfer routes and the start-import/export MCP tools register only when Impex is active.
- Tests and CI: new `WithoutAtrium` suite (`phpunit.without-atrium.xml`, `test:without-atrium` script, `without-atrium` CI job), an arch rule keeping Atrium classes inside `src/Atrium`, `ResetsScreenExtensions` in `TestCase`, and updated docs and changelog.

## Why
Atrium PR #16 adds a screen extensions registry, so other packages (Keen History, Impex run link) add tabs and sections to Showroom pages without Showroom knowing them. Showroom therefore stops rendering other packages' UI and stops requiring Atrium. Decisions that shaped it:
- D-020: prove the package works without Atrium, and conflict with old Atrium versions.
- D-022, D-024, D-026: `permission()` extensions follow `showroom.authorization`; with it off they all show.
- D-023, D-025, D-027: `row.actions` is a trailing cell on transfers too.
- D-001: the asset tile keeps its `lg:col-span-3` column.

## Look at
- `src/Atrium/Support/Screens.php:55`: the access closure. It decides whether the record or the subject class reaches the Authorizer, so Impex's `RunModel` never does.
- `src/Atrium/Http/Controllers/Concerns/AuthorizesScreens.php:25`: your own authorization must run before any extension resolves.
- `composer.json:21`: the Atrium dependency move and the `conflict` constraint `<0.1.0`. It is a placeholder until the first Atrium release carrying the registry.
- `src/Mcp/ShowroomServer.php:252`: `createContext()` filters out the transfer tools when Impex is inactive.
- `resources/views/ui/transfers/index.blade.php` and `resources/views/ui/assets/tile.blade.php`: the trailing cell and the tile `row.actions` layout.

## Tested
- Criteria 1 to 5 (declared screens, access closure, 401/403 before closures run, layout order, nothing registered): `tests/Feature/Ui/ScreenExtensionsTest.php` (dataset over the 25 screens) and `tests/Feature/PerformanceTest.php` (query counts).
- Criterion 6: `tests/Feature/Ui/ShowroomPluginTest.php` and `tests/Feature/DocsTest.php`.
- Criterion 7: `tests/Feature/Ui/TransferPagesTest.php`.
- Criterion 8: `tests/Feature/Ui/ScreenIsolationTest.php`.
- Criterion 9: `tests/Feature/ComposerTest.php`. Criterion 10: `tests/ArchTest.php`.
- Criterion 11: `tests/WithoutAtrium/WithoutAtriumTest.php`.
- Criterion 12: `tests/Feature/DocsTest.php`.
- Gate, round 2: PASS. phpstan clean, pint clean, pest 712/712, WithoutAtrium 6/6.
- Deleted tests, as the spec requires: `HistoryNavigationTest`, `AuditTrailTest`, the Keen audit-trail UI tests, and the `TransferPagesTest` Impex run-link test.

## Risks
- This merges as a group with Atrium PR #16, Keen and Impex. Alone, History and the run link disappear from Showroom.
- Atrium moves to `require-dev`. Apps relying on Showroom to pull in Atrium must now install it themselves. The changelog says so.
- The `conflict` bound `<0.1.0` is provisional and must be set from the first Atrium release carrying the registry.
- Published Atrium views become stale and need re-publishing.
- The transfer routes and MCP tools disappear when Impex is inactive, a behaviour change for apps without Impex.
- With `showroom.authorization` off, every `permission()` extension shows (D-022).
- Row closures run once per row and must not query.
- Open items: none (nits: 0).
