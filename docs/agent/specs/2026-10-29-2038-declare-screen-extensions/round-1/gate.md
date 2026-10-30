VERDICT: CHANGES

## Gate results

- `composer test`: FAILED. phpstan and pint passed. Pest ran 712 tests; 196 failed and 474 passed. The failures seen are HTTP 500s: `Call to undefined method RefactorCircus\Impex\Atrium\ImpexPlugin::historyNavItem()` at `/Users/jay/Projects/packages/Impex/src/Atrium/ImpexPlugin.php:107`, reached through Atrium's `NavigationRegistry::itemsFor()` (the sibling Atrium registry checkout no longer has `historyNavItem()`; the sibling Impex checkout still calls it). Example: `ProductPagesTest` "it creates a product and edits its values". The output was truncated, so the other failures were not individually checked. The without-atrium step of `composer test` was not reached or not reported.
- `composer lint:check`: passed (pint).
- `composer exec ringside check`: FAILED. "Generated files are stale or missing. Run: vendor/bin/ringside sync".

## Criteria

Not proven for 1-8 and 11-13, because the suite fails. Mapping is by the files in the diff.

1. `tests/Feature/Ui/ScreenExtensionsTest.php` (not verified; failing suite)
2. `tests/Feature/Ui/ScreenExtensionsTest.php` and `src/Atrium/Support/Screens.php` (not verified)
3. `tests/Feature/Ui/ScreenIsolationTest.php` (not verified)
4. `tests/Feature/Ui/ScreenExtensionsTest.php` (not verified)
5. `tests/Feature/Ui/ScreenExtensionsTest.php`, `tests/Feature/PerformanceTest.php` (not verified)
6. `tests/Feature/DocsTest.php`; `HistoryNavigationTest.php` and `AuditTrailTest.php` are in the diff (deleted per spec)
7. `tests/Feature/Ui/TransferPagesTest.php` (not verified)
8. `tests/TestCase.php`, `tests/Feature/Ui/ScreenIsolationTest.php`
9. `tests/Feature/ComposerTest.php`, `composer.json`. `composer.local.json` is not in the diff (it may be gitignored).
10. `tests/ArchTest.php`
11. `tests/WithoutAtrium/*`, `phpunit.without-atrium.xml`, `.github/workflows/tests.yml` (not verified)
12. `tests/Feature/DocsTest.php`, `README.md`, `CHANGELOG.md`
13. Gate not green.

## Blockers

1. Pest: 196 failures, from Impex `ImpexPlugin::historyNavItem()` being undefined (an environment mismatch with the sibling Impex checkout, or the Impex group change not yet merged). Resolve by aligning Impex with the Atrium registry branch, or by making the Showroom tests not depend on it.
2. Ringside: generated files are stale. Run `vendor/bin/ringside sync` and commit the result.
