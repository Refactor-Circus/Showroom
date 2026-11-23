Risk: high

## What changed
- Atrium removed: `src/Atrium`, all Blade views, the Atrium/Pennant tests, the without-Atrium lane and `ProductStatus::badge()`. `ShowroomServiceProvider` no longer registers the plugin, views or Blade directive.
- New read-only `Catalog` domain: `GET showroom/stats`, `GET showroom/search`, `GET showroom/settings-summary`, with Actions, events, resources, and MCP tools for stats and search.
- Config: `showroom.ui.*` and `showroom.atrium.*` removed; new `showroom.stats.cache_seconds`. `composer.json` drops Atrium and PennantPlus and adds `dedoc/scramble` (dev) plus the `openapi:export` and `client:generate` scripts.
- Generated artifacts: committed `openapi.json` and `client/` (`@refactor-circus/showroom-client` 2.0.0, with `dist/`). Workbench holds the Scramble config and extension; CI gets a `client` job.
- Docs, CHANGELOG (v2.0.0 upgrade note), README, CLAUDE.md and the Boost skill are updated.

## Why
Showroom v2.0.0 ships only its JSON API and MCP server. Data the screens computed in PHP becomes API, and a committed OpenAPI document plus typed client lets Atrium v2's Nuxt layer consume it. Decisions that shaped it:
- D-001: no transfers endpoint.
- D-003: one four-section stats payload.
- D-004: a Catalog domain.
- D-005: `viewAny` per type for authorization.
- D-006: no MCP tool for the settings summary.
- D-008: stale-artifact test without Node.
- D-014: config removal allowed in a major release.

## Look at
- `src/Domains/Catalog/Http/Requests/SearchCatalogRequest.php:20`: authorization for search (403 only when no requested type is allowed, 401 for guests).
- `src/Domains/Catalog/Actions/SearchCatalogAction.php:34`: per-type `viewAny` filtering, matching and ordering carried over from the plugin.
- `src/Domains/Catalog/Actions/ShowCatalogStatsAction.php:110`: completeness cache and constant query count.
- `composer.json` and `config/showroom.php`: breaking removals and the new `stats` section.
- `workbench/app/OpenApi/ResourceResponses.php:1`: the Scramble extension that determines `openapi.json` response schemas and its determinism.

## Tested
Gate round 1 passed: `composer test` (phpstan, pint, pest 1091/1091, 100% type coverage), `lint:check` and ringside check.
- AC 1-3: `ShowCatalogStatsActionTest`, `CatalogStatsApiTest`, `PerformanceTest`.
- AC 4-5: `SearchCatalogActionTest`, `CatalogSearchApiTest`.
- AC 6-7: `ShowSettingsSummaryActionTest`, `SettingsSummaryApiTest`.
- AC 8-9: `EventsTest`, `ArchTest`, the MCP tool tests, `OutputSchemaTest` and the `PARITY_EXCEPTIONS` entry.
- AC 11-18: `NoAtriumReferencesTest`, `ComposerTest`, `ServiceProviderTest` and the deletions.
- AC 19-23: `tests/OpenApi/OpenApiTest.php`.
- AC 24-26: `DocsTest`.

## Risks
- Breaking for hosts that published the views, set `showroom.atrium.*` or `showroom.ui.*`, used `ShowroomSupportFeature`, or called `ProductStatus::badge()`. The CHANGELOG upgrade note covers each.
- Cortex, Impex and Keen still pull Atrium transitively, so `tests/TestCase.php` may keep `atrium.disabled`.
- Scramble output must stay byte-stable, or the staleness test flakes.
- Existing endpoints' response schemas are best effort, which limits client typing for them.
- The `client` CI job (Node) is not exercised by the local gate.
- Nits: 1 (see the round-1 review files).
