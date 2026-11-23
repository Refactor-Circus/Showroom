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
