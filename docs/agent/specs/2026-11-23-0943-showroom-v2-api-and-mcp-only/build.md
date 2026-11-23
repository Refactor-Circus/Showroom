# Build: Showroom v2: API and MCP only

## Group 1 (salvaged)

Found: the build agent had written every file of the group but committed nothing. No index lock and no merge or rebase in progress. The Catalog domain (provider, `routes.php`, controller, `ShowCatalogStatsRequest`, `ShowCatalogStatsAction`, `CatalogStats`, the Showing/Shown event pair, `CatalogStatsResource` with `schema()`, the MCP request and `show-catalog-stats-tool`), the `stats` config banner, the provider and server registrations, and the tests (`ShowCatalogStatsActionTest`, `CatalogStatsApiTest`, `CatalogStatsToolTest`, the stats case in `PerformanceTest`, the event pair in `EventsTest`) were all in the worktree. They match the spec's Design and D-003/D-004/D-005, and the queries are the ones ShowroomPlugin used (`scope` renamed to `channel`).

Checked: Pint passes on the group's files. The group's own test files, `OutputSchemaTest`, `McpTest`, `ArchTest`, `tests/Cortex/CortexTest.php` and the stats case in `PerformanceTest` pass (stats call pinned at 5 queries for 10 and 200 products). The full suite was not run here; the gate runs it (task 1.6).

Committed: only the 19 paths the group lists.

Left uncommitted: the agent also edited four files outside the group's list, which the passing tests above need:

- `tests/Feature/McpTest.php`, `tests/Feature/Mcp/OutputSchemaTest.php` and `tests/Cortex/CortexTest.php`: tool counts from 83 to 84;
- `tests/Pest.php`: `'ShowCatalogStatsAction' => ['ui']` in `PARITY_EXCEPTIONS` (the Atrium screens never reach it; group 3 drops `ui`).

Without them, the committed branch fails those counts and the parity check. They are left in the worktree for a decision on where they get committed.

Left: 1.6 (full `vendor/bin/pest`), which the gate runs.

## Group 2

Files:

- Search: `src/Domains/Catalog/Enums/SearchType.php` (7 values in result order, `model()`, `requested()` for the types a request names), `Data/SearchCatalogData.php` (`q`, `?SearchType $type`, `int $limit = 5`, `rules()`, `types()`), `Data/SearchHit.php` (the `final readonly` hit the spec's Design names), `Actions/SearchCatalogAction.php`, `Events/CatalogSearchingActionEvent.php` (carries the Data), `Events/CatalogSearchedActionEvent.php` (Data and hits), `Resources/CatalogSearchResultResource.php`, `Http/Requests/SearchCatalogRequest.php`, `Mcp/Requests/SearchCatalogMcpRequest.php`, `Mcp/Tools/SearchCatalogTool.php`.
- Settings summary: `Actions/ShowSettingsSummaryAction.php` (a `Summary` phpstan type), `Events/SettingsSummaryShowingActionEvent.php`, `Events/SettingsSummaryShownActionEvent.php`, `Resources/SettingsSummaryResource.php`, `Http/Requests/ShowSettingsSummaryRequest.php`.
- `routes.php` (`search.index`, `settings-summary.show`), `CatalogController::search()` and `settingsSummary()`, `src/Mcp/ShowroomServer.php` (tool registered, instructions name it).
- Tests: `tests/Feature/Catalog/SearchCatalogActionTest.php`, `tests/Feature/Catalog/ShowSettingsSummaryActionTest.php`, `tests/Feature/Api/CatalogSearchApiTest.php`, `tests/Feature/Api/SettingsSummaryApiTest.php`, `tests/Feature/Mcp/SearchCatalogToolTest.php`, `tests/Feature/EventsTest.php` (Action count 86 to 88, both pairs), `tests/Pest.php` (parity exceptions).

Behavior: matching and order are the ShowroomPlugin search's: products by identifier prefix ordered by identifier, assets by `search()` or filename newest first, the rest by `search()` ordered by code. Subtitles are the plugin's too (asset filename, `code · type` for owners and attributes, the code otherwise, null for products). Types the caller may not `viewAny` are skipped through `Authorizer::for(package)->can()`. Both requests refuse when no requested type is allowed. They run that check before validation, so an unknown `type` counts as every type there and validation then answers 422. Settings values are cast the way the package reads them: switches are on only for `true`, middleware becomes a list of names, page sizes fall back to the default when not numeric (as `Paging` does) and strings are cast.

Checked: Pint and PHPStan pass on `src/Domains/Catalog` and `src/Mcp`. The suite was not run here; the gate runs it (task 2.5).

Deviations:

- `src/Domains/Catalog/Data/SearchHit.php` is not in the group's file list. The spec's Design names it (`Collection<SearchHit>`).
- Outside the list, the tool count goes from 84 to 85 in `tests/Feature/McpTest.php`, `tests/Feature/Mcp/OutputSchemaTest.php` and `tests/Cortex/CortexTest.php`, as D-016 did for group 1.
- `tests/Pest.php` also gets `'SearchCatalogAction' => ['ui']`, besides the listed `'ShowSettingsSummaryAction' => ['mcp', 'ui']`. The Atrium screens never reach it, as with `ShowCatalogStatsAction`; group 3 drops `ui`.

## Group 2 (salvaged)

Found: the build agent had written every file of the group, ticked 2.1 to 2.5 and written the section above, but committed nothing. No index lock and no merge or rebase in progress. The sources, tests, routes, controller methods, server registration, `EventsTest` pairs and parity exceptions match the spec's Design and D-005/D-006/D-007: matching and order per type as the plugin's search, `SearchType` in result order with `model()`, `SearchCatalogData` (`q` required max 191, `type` nullable enum, `limit` 1..20 default 5), types filtered through `Authorizer::for(package)->can()`, both search requests refusing when no requested type is allowed, and the settings summary cast from config.

Checked: Pint passes on the group's files. PHPStan could not be checked here (run from the worktree it does not find the other domains' model classes, an autoload issue of this checkout, not of the code). The suite was not run; the gate runs it.

Committed: only the 24 paths the group lists, with tasks 2.1 to 2.4 ticked. Task 2.5 was unticked, since the suite was never run.

Left uncommitted, outside the group's list, which the committed code and tests need:

- `src/Domains/Catalog/Data/SearchHit.php`: the hit class `SearchCatalogAction`, its finished event, the resource and the tests use (the spec's Design names it as `Collection<SearchHit>`). Without it the committed branch does not load the search classes.
- `tests/Feature/McpTest.php`, `tests/Feature/Mcp/OutputSchemaTest.php` and `tests/Cortex/CortexTest.php`: tool counts from 84 to 85, as D-016 did for group 1.

They are left in the worktree for a decision on where they get committed.

Left: 2.5 (full `vendor/bin/pest`), which the gate runs.

## Group 3

Files:

- Tests first: `tests/ArchTest.php` (the Atrium rule is now "no Showroom class uses an Atrium class" with no ignore list, `TransferUiController` is off the Impex rule's ignore list, the parity test is "every use case is reachable from the HTTP API and MCP"); `tests/Pest.php` (`parityGaps()` checks `http` and `mcp` only; every `ui` entry is gone from `PARITY_EXCEPTIONS`, and the entries that held only `ui` are dropped); `tests/Feature/ServiceProviderTest.php` (no `showroom-views` tag in the publish list or `publishableGroups()`, no `showroom` view hint, no `showroomCan`/`endshowroomCan` directive, no `ProductStatus::badge()`); `tests/Feature/PerformanceTest.php` (screen and widget cases and their imports removed; group 1's stats case stays); `tests/Feature/RecordLookupTest.php` (the `atrium.showroom.*` rows and route filter removed; the dashboard-link case keeps only its API half, renamed "links records by the configured key"; the `beforeEach` that set CSRF and the local environment for Atrium's screens is gone).
- Deleted: `tests/Feature/Ui/**`, `tests/Feature/NavigationSectionTest.php`, `src/Atrium/**` except `src/Atrium/Features/ShowroomSupportFeature.php`, `resources/views/**`, `docs/agent/standards/showroom/atrium-screens.md`.
- `src/ShowroomServiceProvider.php`: no `registerAtriumPlugin()`, `loadViewsFrom()`, `Blade::if('showroomCan')` or views publish entry, and their imports. Translations, the other publish tags, `loadHistoryRoutes()` and the Impex integration stay.
- `src/Domains/Product/Enums/ProductStatus.php`: no `badge()` and no `Badges` import.

Tests: Pint passes on every changed PHP file. Run here as spot checks, not the full suite: `tests/ArchTest.php` with `tests/Feature/PerformanceTest.php` (33 passed), `tests/Feature/RecordLookupTest.php --parallel` (173 passed), `tests/Feature/ServiceProviderTest.php` (25 passed) and `tests/PennantPlus/ShowroomSupportFeatureTest.php` (1 passed). Task 3.5, the full `vendor/bin/pest`, is left to the gate. Running ArchTest, RecordLookupTest and PerformanceTest together in one serial process ran out of memory (512 MB) while loading routes. The project's `test:unit` runs `--parallel`, and each file passes alone.

Deviations:

- `tests/PennantPlus/ShowroomSupportFeatureTest.php` is outside the group's list. Its cases tested `ShowroomPlugin::features()` and the Atrium navigation and `atrium.showroom.*` routes, which this group deletes, and the `PennantPlus` suite in `phpunit.xml.dist` would fail without it. It now holds one case for the feature alone (on until its global value is turned off). Group 4 deletes the file with `tests/PennantPlus/**`.
- `docs/agent/standards.index.yml` still listed `showroom/atrium-screens`. It is generated and git-ignored, so `vendor/bin/ringside sync` rebuilt it locally and nothing is committed for it.
- Left for later groups (their lists own them): `README.md` still documents `--tag="showroom-views"`, which `DocsTest` asserts (group 6). The `trait_exists(ResetsScreenExtensions)` guard in `tests/Pest.php` stays until group 4. Group 4 deletes `tests/PennantPlus/**` but does not list `phpunit.xml.dist`, which still declares the `PennantPlus` testsuite directory.

## Group 3 (retry)

Found: tasks 3.1 to 3.5 were already done and committed (`bf382e6`), with a clean worktree. Checked again: no file under `src/`, `tests/`, `workbench/` or `config/` names a deleted class, the `showroom::` views, `@showroomCan`, `badge()` or an `atrium.showroom.*` route. `tests/ArchTest.php` and `tests/Feature/ServiceProviderTest.php` pass (45 tests). No code changed.

Still left for group 4, which owns the files: `composer.json` still declares `extra.atrium.plugins` → `RefactorCircus\Showroom\Atrium\ShowroomPlugin`, a class that no longer exists. Tests are unaffected, because Atrium's discovery reads only `vendor/composer/installed.json`, which leaves out the root package. A host app that installs this branch with Atrium v1 would fail to discover the plugin until group 4 removes the entry. `testbench.yaml`'s `start: '/atrium/showroom/products'` (used by `composer serve` only) is also group 4's.

## Group 4

Files:

- Tests first: `tests/Feature/ComposerTest.php` (rewritten: Keystone the only suite package; `require` only `php`, `laravel/*`/`illuminate/*` and Keystone; no `refactor-circus/atrium` in `require`, `require-dev`, `suggest` or `conflict`; no `extra.atrium`; no Atrium or PennantPlus repository; no `refactor-circus/pennantplus`; no `test:without-atrium` and `test` is exactly analyse, lint:check, test:types, test:unit; the Keen suggest names no Atrium). `tests/Feature/NoAtriumReferencesTest.php` (new: no file under `src/`, `config/`, `resources/`, `workbench/` or `composer.json` contains "atrium", case-insensitive; the workbench's git-ignored `storage/`, `.env` and `.env.dusk` are skipped; a second case proves the scan reaches the source, config and workbench). `tests/Feature/ServiceProviderTest.php` (config has no `ui` or `atrium` key, `showroom.stats.cache_seconds` is 60).
- `composer.json`: Atrium off `require-dev`, `suggest` and `conflict` (the `conflict` block is gone); `extra.atrium` and the Atrium VCS repository removed; `refactor-circus/pennantplus` off `require-dev` and `suggest`, and its VCS repository removed with it (D-011); the Keen suggest now reads "serve each record's history over the API and MCP"; `test:without-atrium` and its `test` entry removed. Keystone constraint unchanged (D-010).
- `config/showroom.php`: the `ui` and `atrium` sections and the `ShowroomSupportFeature` import are gone (D-014); the Authorization and Audit banners no longer mention Atrium screens.
- Deleted: `src/Atrium/Features/ShowroomSupportFeature.php` (`src/Atrium` is gone), `tests/PennantPlus/**`, `tests/PennantPlusTestCase.php`, `tests/Fixtures/OrphanSupportFeature.php`, `phpunit.without-atrium.xml`, `tests/WithoutAtrium/**`.
- Harness: `tests/Pest.php` registers its test cases unconditionally (no `trait_exists(ResetsScreenExtensions)` guard, no PennantPlus `uses()`). `tests/TestCase.php` drops `AtriumServiceProvider`, `ResetsScreenExtensions` and `atrium.disabled`: Cortex, Impex and Keen list Atrium only in their own `require-dev`, which Composer never installs for a dependency, so after the update Atrium is not installed and nothing needs disabling (the spec's Risks assumed a transitive install). `testbench.yaml` drops the Atrium provider, the Pennant migrations, `atrium-assets`, and starts at `/showroom/products`. `WorkbenchServiceProvider` drops the pennantplus store, `impex.atrium.show_all` and the `viewAtrium` gate. `.github/workflows/tests.yml` drops the `without-atrium` job.
- Dependencies: `composer.local.json` regenerated (`bin/composer-local.php`, suite constraints at `dev-main` as the factory's local install has them) and `composer update refactor-circus/atrium refactor-circus/pennantplus laravel/pennant -w` removed Atrium, PennantPlus and Pennant from `vendor/`. Both files are git-ignored.

Tests: spot checks, not the full suite (the gate runs task 4.5): `ComposerTest`, `NoAtriumReferencesTest` and `ServiceProviderTest` (42 passed), `tests/Cortex` (7 passed) and `tests/Keen` (5 passed) with Atrium uninstalled, `tests/ArchTest.php` (20 passed). Pint passes on every changed PHP file; PHPStan passes on `config`.

Deviations (files outside the group's list):

- `phpunit.xml.dist`: the `PennantPlus` testsuite is removed; its directory is deleted by this group.
- `resources/boost/skills/showroom-development/SKILL.md`: the Atrium mentions (description, intro, the six screen bullets under "Secure the surfaces", and `ui.enabled`/`atrium.*` in the config key list, now `stats.cache_seconds`) are removed, because `NoAtriumReferencesTest` scans `resources/`. Group 6 still rewrites the skill as API + MCP.
- `docs/13-configuration.md`: the `ui.enabled`, `atrium.features` and `atrium.widget_cache_seconds` rows are replaced by a `stats.cache_seconds` row, so `DocsTest`'s two config-table checks match the shipped config. Group 6 owns the rest of the page.

Known failure not caused by this group: `DocsTest` "mentions no removed audit trail, History nav item or Impex run link" errors because `docs/agent/standards` no longer exists (group 3 deleted its only file, `showroom/atrium-screens.md`), and the test passes that directory to `Finder::in()`. `tests/Feature/DocsTest.php` is group 6's file (6.1), so it is left for that group or a fix round: drop the directory from the list, or skip it when missing.

## Group 4 (retry)

Found: tasks 4.1 to 4.5 were already done and committed (`9be04e5`), with a clean worktree. Checked again: no file under `src/`, `config/`, `resources/`, `workbench/` or `composer.json` mentions "atrium". No harness file, workflow, `phpunit.xml.dist` or `composer.json` mentions PennantPlus, `ShowroomSupportFeature`, `ResetsScreenExtensions` or the without-Atrium suite. `src/Atrium`, `tests/PennantPlus`, `tests/WithoutAtrium`, `tests/PennantPlusTestCase.php`, `tests/Fixtures/OrphanSupportFeature.php` and `phpunit.without-atrium.xml` are gone.

Fixed: the one known failure. `tests/Feature/DocsTest.php` "mentions no removed audit trail, History nav item or Impex run link" no longer passes the deleted `docs/agent/standards` directory to `Finder::in()`.

Tests: `ComposerTest`, `NoAtriumReferencesTest`, `ServiceProviderTest` and `DocsTest` pass (90 tests). Pint passes on `DocsTest`. The gate runs the full suite (task 4.5).

Deviations: `tests/Feature/DocsTest.php` is outside the group's list (group 6 owns it). The one-line fix is here so the suite is green after this group.

## Group 5 (salvaged)

Found: no index lock and no merge or rebase in progress. The build agent had written all of tasks 5.1 to 5.4 but committed none of them. Its uncommitted work:

- `tests/OpenApi/OpenApiTest.php` (14 cases): a fresh in-process `scramble:export` equals the committed `openapi.json`; the document is OpenAPI 3.1, `Showroom` 2.0.0, with the relative server `/showroom`; every default `showroom.*` API route's method and path is documented, and nothing else is; operation ids are route names; the stats, search and settings-summary 200 responses carry their resource `schema()`'s required keys; search answers a list and lists `q` (required), `type` and `limit`; line 1 of `client/src/schema.d.ts` stamps the sha256 of `openapi.json`; `client/dist/schema.d.ts` equals the source; `dist/index.{js,d.ts}` exist; `client/package.json` has the name, version, dependencies, `main`/`types`/`exports` in `dist` and the `generate` script; `.gitignore`/`.gitattributes` keep `client/node_modules` out. `tests/OpenApiTestCase.php` boots the workbench with package discovery and `ScrambleServiceProvider`; `tests/Pest.php` binds it to `OpenApi`.
- `composer.json`: `dedoc/scramble` in `require-dev`; `openapi:export` and `client:generate` scripts.
- `workbench/config/scramble.php` (`api_path` showroom, `info.version` 2.0.0, fixed values only). `WorkbenchServiceProvider` loads it, sets operation ids to route names, sets the relative `/showroom` server and makes `scramble:export` read an in-memory database so the export never depends on a migrated workbench. `workbench/app/OpenApi/ResourceResponses.php` writes the 200 response of the three `persist()` endpoints from their resources' `schema()`.
- `client/`: `package.json`, `package-lock.json`, `tsconfig.json`, `scripts/stamp.mjs`, `src/index.ts` (`createShowroomClient({ baseUrl, headers?, fetch?, credentials? })` returning `Client<paths>`, re-exporting `paths`/`components`/`operations`), the generated `src/schema.d.ts`, `dist/**` and `README.md`. `.gitignore` and `.gitattributes` exclude `client/node_modules`. `.github/workflows/tests.yml` has a `client` job (Node `lts/*`) running `composer client:generate` and `git diff --exit-code`.

Checked: `composer client:generate` ran again and changed nothing: `openapi.json` has the same sha256, `src/schema.d.ts` still carries the matching stamp and equals `dist/schema.d.ts`. The export's 19 warnings ("Cannot read database schema", one "Cannot infer the resource model") come from the deliberately empty database and are expected. `vendor/bin/pest tests/OpenApi` passed: 14 tests, 81 assertions.

Committed: every path above, all from the group's list.

Left:

- Task 5.5 (the full `vendor/bin/pest`) is for the gate.
- `phpunit.xml.dist` is not in the group's list, so it stays uncommitted in the worktree. The build agent added an `OpenApi` testsuite (`tests/OpenApi`) to it. Without that suite, `vendor/bin/pest` never runs `OpenApiTest`, so the staleness checks for criteria 19 to 23 do not run in the suite. This is returned as a decision (commit it as a group 5 follow-up, as D-016 and D-017 did).
- Nit: `actions/setup-node@v7` in the `client` job is pinned by tag, while the other actions are pinned by SHA.

## Group 6

Files:

- `tests/Feature/DocsTest.php`: dropped the 27-screen dataset, the slot, `permission()` and row-closure cases, "says Atrium is optional" and the Atrium upgrade case. New cases: the README is a JSON API and MCP package with no "Dashboard" or "Screen extensions" heading and no `showroom-views`, `showroomCan`, `ShowroomSupportFeature` or `x-atrium`; "Front end: Atrium v2" says Atrium is optional, lists runs through Impex's runs API by `flow`, keeps `POST /showroom/imports` and `POST /showroom/exports` and has no transfer record (D-001); "OpenAPI and TypeScript client" names `openapi.json`, `client/`, the package name, `composer client:generate`, `OpenApiTest`, the `#v2.0.0&path:client` git dependency and `createShowroomClient`; "HTTP API" lists the three endpoints; `docs/11-dashboard.md` is gone and unlinked; `docs/09-api.md` documents stats, search and settings-summary; `docs/10-mcp.md` names both tools; no guide under `docs/` mentions Atrium, "the dashboard", the views tag, `@showroomCan`, `ShowroomSupportFeature`, `viewAtrium`, `showroom.ui.` or Pennant; the CHANGELOG has no `[Unreleased]`, heads v2.0.0, and its "Upgrading to v2.0.0" note names every removal, both stats keys, Atrium v2 and pennantplus, and its Added names the endpoints, tools, `openapi.json` and `client/`; the Boost skill and CLAUDE.md name the JSON API and MCP, `Domains\Catalog`, `composer client:generate` and `OpenApiTest` and none of the Atrium screen conventions; CLAUDE.md's config rule allows removal only in a major release with an upgrade note (D-014). The two changelog cases that read `[Unreleased]` now read the v2.0.0 section (`showroomNextRelease()`).
- `README.md`: rewritten as API + MCP. Installation drops the views tag and the Atrium install. The HTTP API table gains the three endpoints, with a paragraph on the `Catalog` domain, caching and authorization. MCP names the two tools. "Dashboard" and "Screen extensions" are replaced by "OpenAPI and TypeScript client" and "Front end: Atrium v2". Code layout gains `Catalog` and loses `src/Atrium`.
- `docs/11-dashboard.md` deleted; `docs/README.md` loses its row. `docs/09-api.md` gains the endpoints and the "Catalog stats", "Catalog search", "Settings summary" and "OpenAPI document" sections. `docs/10-mcp.md` gains both tools and the settings-summary parity exception. `docs/13-configuration.md` drops Atrium from `authorization` and `lookup.products` (`stats.cache_seconds` was already there from group 1). Atrium screen lines removed from `docs/01`, `03`, `04`, `06` and `14`.
- `CHANGELOG.md`: the next release is `[v2.0.0] - Unreleased`, opening with "Upgrading to v2.0.0" and Added entries for the endpoints, the `Catalog` domain, the MCP tools, `openapi.json` and `client/`. The pre-v2 entries that described Atrium screens (lookup, family form, flag and owner screens) lose those parts, since v2.0.0 ships no screens.
- `resources/boost/skills/showroom-development/SKILL.md`: API + MCP description, Catalog Actions and endpoints, runs through Impex, a "Call the API from TypeScript" step with `client:generate` and `OpenApiTest`. It names no Atrium, since `NoAtriumReferencesTest` scans `resources/`.
- `CLAUDE.md`: API + MCP intro; `registerAtriumPlugin()` and views dropped from the provider bullet; the Atrium controller Data note, the `src/Atrium` line, the `ShowroomSupportFeature` name, the styles, `AuthorizesScreens`, screen-extensions and optional-Atrium bullets removed; new `Catalog` and OpenAPI/client bullets and a "No UI and no Atrium" bullet (which keeps the `AuditHooks::label()` sentence); parity over HTTP and MCP; config rule amended (D-014); `client:generate` and `openapi:export` in Quick Commands.

Tests: `vendor/bin/pest tests/Feature/DocsTest.php tests/Feature/NoAtriumReferencesTest.php` passed (28 tests, 216 assertions). Pint passes on `DocsTest`. The full `composer test` is for the gate (task 6.5).

Deviations: `docs/02-attributes.md`, `docs/05-search.md`, `docs/08-media.md` and `docs/12-impex.md` are outside the group's list. Criterion 24 says the other `docs/*.md` lose their Atrium screen references, and these four named the removed dashboard: 02, 05 and 08 lose "the dashboard" from a list of surfaces, 12 says Impex's API and MCP tools instead of "dashboard", and its "Dashboard" section (the removed Catalog → Import & export page) becomes "Listing runs" (Impex's runs API by `flow`, D-001).

`CLAUDE.md` is a symlink to `AGENTS.md`, so the CLAUDE.md changes are committed as `AGENTS.md`.
