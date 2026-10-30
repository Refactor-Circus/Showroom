# Tasks: Declare screen extensions (Showroom)

## Group 1: Screen declarations
package: Showroom
depends on: none
files: src/Atrium/Support/Screens.php, src/Atrium/Http/Controllers/*, src/Atrium/ShowroomPlugin.php, resources/views/ui/**, tests/TestCase.php, tests/Feature/Ui/ScreenExtensionsTest.php, tests/Feature/Ui/ScreenIsolationTest.php, tests/Feature/Ui/TransferPagesTest.php, tests/Feature/Ui/AuditTrailTest.php, tests/Feature/HistoryNavigationTest.php, tests/Feature/PerformanceTest.php, tests/Keen/*
criteria: 1, 2, 3, 4, 5, 6, 7, 8

- [x] 1.1 Write the screen tests: declaration and layout dataset over the 25 screens, access closure, 403/401 zero-call dataset, nothing-registered dataset and query counts, audit-trail and nav removal, transfers row slot, and the ordered isolation pair.
- [x] 1.2 Add `ResetsScreenExtensions` to `tests/TestCase.php` and the `Atrium\Support\Screens` helper with the criterion 2 access closure.
- [x] 1.3 Resolve the screen in every controller after its own check and pass `resolved` to the view.
- [x] 1.4 Lay out tabs, panel and slots in every view per criterion 4, remove every `x-atrium::audit-trail` and the Impex run link, and drop `historyNavItem()` from `ShowroomPlugin::navigation()`.
- [x] 1.5 Delete the forced tests (`HistoryNavigationTest`, `AuditTrailTest`, the Keen audit-trail UI tests, the Impex run-link transfer test) and list them in the PR.
- [x] 1.6 Run vendor/bin/pest; all green.

## Group 2: Atrium optional
package: Showroom
depends on: none
files: composer.json, composer.local.json, tests/ArchTest.php, tests/Feature/ComposerTest.php, phpunit.without-atrium.xml, tests/WithoutAtrium/*, src/Mcp/ShowroomServer.php, src/ShowroomServiceProvider.php, .github/workflows/tests.yml
criteria: 9, 10, 11

- [x] 2.1 Write the composer.json test, the arch rule and the WithoutAtrium suite (bootstrap, test case, boot, routes, JSON API, MCP assertions).
- [x] 2.2 Move Atrium to `require-dev` and `suggest`, add the `conflict` entry, regenerate `composer.local.json`, and add the `test:without-atrium` script to `composer test`.
- [x] 2.3 Guard the transfer MCP tools and JSON routes on `ImpexIntegration::active()` if they are not already, and add the `without-atrium` CI job.
- [x] 2.4 Run vendor/bin/pest; all green.

## Group 3: Docs and gate
package: Showroom
depends on: 1, 2
files: README.md, CHANGELOG.md, CLAUDE.md, resources/boost/skills/showroom-development/SKILL.md, docs/agent/standards/showroom/atrium-screens.md, tests/Feature/DocsTest.php
criteria: 12, 13

- [ ] 3.1 Write the docs test for the 25 keys, the authorization and row-closure rules, Atrium optional, and the absence of `audit-trail`, `historyNavItem` and `atrium.impex`.
- [ ] 3.2 Update the README, changelog, CLAUDE.md, Boost skill and the atrium-screens standard.
- [ ] 3.3 Run composer test and vendor/bin/pest; all green.
