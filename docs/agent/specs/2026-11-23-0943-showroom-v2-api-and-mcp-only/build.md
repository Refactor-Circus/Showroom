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
