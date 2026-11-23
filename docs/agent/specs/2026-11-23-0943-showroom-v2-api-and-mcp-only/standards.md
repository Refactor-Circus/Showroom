# Standards for Showroom v2: API and MCP only

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| http/routes | vendor/refactor-circus/standards/standards/http/routes.md | refactor-circus/standards@dev-main | New `Catalog` domain `routes.php` loaded through `loadApiRoutesFrom()`; routes named `showroom.stats.show`, `showroom.search.index`, `showroom.settings-summary.show` |
| http/request-persist | vendor/refactor-circus/standards/standards/http/request-persist.md | refactor-circus/standards@dev-main | `ShowCatalogStatsRequest`, `SearchCatalogRequest`, `ShowSettingsSummaryRequest` validate and `persist()` through their Actions |
| http/resources | vendor/refactor-circus/standards/standards/http/resources.md | refactor-circus/standards@dev-main | New final `CatalogStatsResource`, `CatalogSearchResultResource`, `SettingsSummaryResource` with `schema()` |
| http/authorization | vendor/refactor-circus/standards/standards/http/authorization.md | refactor-circus/standards@dev-main | Stats and settings summary ask `viewAny` on products; search asks `viewAny` per searched record type (D-005) |
| actions/actions-events | vendor/refactor-circus/standards/standards/actions/actions-events.md | refactor-circus/standards@dev-main | Three new read Actions, each with its starting/finished event pair (Showroom's arch rule) |
| data/data-objects | vendor/refactor-circus/standards/standards/data/data-objects.md | refactor-circus/standards@dev-main | New `SearchCatalogData` (`q`, `type`, `limit`) with `rules()`; `CatalogStats` result value |
| architecture/domain-modules | vendor/refactor-circus/standards/standards/architecture/domain-modules.md | refactor-circus/standards@dev-main | New `src/Domains/Catalog` module and `CatalogServiceProvider` in `DomainServiceProvider` (D-004) |
| architecture/naming | vendor/refactor-circus/standards/standards/architecture/naming.md | refactor-circus/standards@dev-main | `Show/Search…Action`, `…Data`, `…Request`, `…Resource`, `…ActionEvent` names |
| architecture/configuration | vendor/refactor-circus/standards/standards/architecture/configuration.md | refactor-circus/standards@dev-main | `showroom.ui` and `showroom.atrium` removed; new `showroom.stats` banner section with cast `cache_seconds` |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | No Atrium reference left; Keystone the only suite `require`; `dedoc/scramble` only in `require-dev` and the workbench |
| architecture/service-providers | vendor/refactor-circus/standards/standards/architecture/service-providers.md | refactor-circus/standards@dev-main | `ShowroomServiceProvider` loses the Atrium plugin, views, Blade directive and views publish tag |
| architecture/public-api | vendor/refactor-circus/standards/standards/architecture/public-api.md | refactor-circus/standards@dev-main | Removed public surface (`ProductStatus::badge()`, `showroom-views` tag, Atrium classes) is a breaking change: v2.0.0 with an upgrade note |
| testing/test-layers | vendor/refactor-circus/standards/standards/testing/test-layers.md | refactor-circus/standards@dev-main | Behavior tested at the Actions; API and MCP tests check wiring, authorization and shape |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | Arch rule that no Showroom class uses `RefactorCircus\Atrium`; parity over HTTP and MCP only |
| showroom/atrium-screens | docs/agent/standards/showroom/atrium-screens.md | local | Retired by this spec: the screens it governs are deleted, so the local standard file is removed |
