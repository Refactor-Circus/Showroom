# Showroom v2: API and MCP only

## Goal

Showroom v2.0.0 ships only its JSON API and MCP server. Its Atrium plugin, Blade screens and Atrium config are deleted. The data the screens computed in PHP becomes API (`stats`, `search`, `settings-summary`), and a committed OpenAPI document and TypeScript client let Showroom's own Atrium module (`atrium/`, built to `dist/atrium` and loaded by Atrium v2's prebuilt Vue app on its Blade page) call the API with types.

## Acceptance criteria

### New API (Catalog domain)

1. `GET showroom/stats` (route `showroom.stats.show`) answers 200 with `CatalogStatsResource`, which has four required keys:
   - `status`: `counts` (one integer per `ProductStatus` value, `0` for a status with no products) and `published` (products with a non-null `published_version`).
   - `completeness`: a list of `{channel, locale, products, complete, average}`, one row per channel and locale with stored scores, ordered by channel code then locale code. `complete` counts ratio 100; `average` is the rounded mean ratio.
   - `review_queue`: `products` (the 5 `in_review` products with the oldest `updated_at`, each `{id, identifier, slug, status, updated_at}`) and `total` (every `in_review` product).
   - `recent_versions`: the 8 newest versions. Each has `VersionSummaryResource`'s fields plus `subject: {type, id, key}`. `type` is `product` or `product_model`, and `key` is the identifier or code.

   An Action test seeds a known catalog and asserts every figure.
2. The `completeness` rows are cached for `showroom.stats.cache_seconds` (default 60) under one cache key. `0` reads them fresh on every call. Tests cover a cached value served within the window and a fresh read at `0`. The other sections are never cached.
3. The stats call runs the same number of queries for 10 products as for 200 (versions and their subjects eager-loaded). A test pins the count.
4. `GET showroom/search?q=&type=&limit=` (route `showroom.search.index`) answers 200 with a list of `CatalogSearchResultResource` items `{type, id, code, label, subtitle}`.
   - `type` is one of `products`, `assets`, `categories`, `owners`, `families`, `attributes`, `attribute_groups`, and the results come in that order.
   - Matching is the same as the deleted Atrium search: products by identifier prefix, assets by their `search()` scope or filename, the others by their `search()` scope.
   - Ordering is the same too: products by identifier, assets newest first, the others by code.
   - `limit` (1 to 20, default 5) caps the results per type.
   - `q` is required (string, at most 191 characters). A missing `q`, an unknown `type` or an out-of-range `limit` answers 422.
   - An optional `type` limits the search to that one record type.
5. Search covers only the record types the caller may `viewAny`, asked through `showroom.policies`. A type the caller may not list adds no results. A caller who may list none of the requested types gets 403, and a guest gets 401 while `showroom.authorization` is on. With authorization off, every type is searched. Tests cover a user allowed only products, a user allowed none, a guest, and `type=` naming a forbidden type.
6. `GET showroom/settings-summary` (route `showroom.settings-summary.show`) answers 200 with `SettingsSummaryResource`. Each value is cast from config, never passed through as a raw array:
   - `authorization` (bool);
   - `routes` `{enabled, prefix, middleware[]}`;
   - `mcp` `{web: {enabled, route, middleware[]}, local: {enabled, handle}}`;
   - `pagination` `{per_page, max_per_page}`.
7. Stats and settings summary require `viewAny` on products: a guest gets 401 and a signed-in user without the ability gets 403 while `showroom.authorization` is on. With authorization off, both answer 200 (D-005).
8. Each new Action (`ShowCatalogStatsAction`, `SearchCatalogAction`, `ShowSettingsSummaryAction`) dispatches its starting and finished event. The events are added to `EventsTest`, and the arch rules on Actions pass.
9. MCP has `show-catalog-stats-tool` and `search-catalog-tool`, which return the same resources as structured responses. Each declares an `outputSchema()` that `OutputSchemaTest` checks. `ShowSettingsSummaryAction` is listed in `PARITY_EXCEPTIONS` for `mcp` with its reason (D-006).
10. Showroom adds no transfers-list endpoint and no transfer record (D-001). The README's Atrium v2 section says that Atrium lists transfer runs through Impex's runs API, and that starting runs stays on `POST showroom/imports` and `POST showroom/exports`.

### Atrium removed

11. `src/Atrium/**`, `resources/views/ui/**` (the whole `resources/views` tree) and `tests/Feature/Ui/**` are deleted. These tests go too: `tests/Feature/NavigationSectionTest.php`, `tests/WithoutAtrium/**`, `phpunit.without-atrium.xml`, `tests/PennantPlus/**`, `tests/PennantPlusTestCase.php` and `tests/Fixtures/OrphanSupportFeature.php`. `PerformanceTest` and `RecordLookupTest` keep only their API and MCP cases.
12. `ShowroomServiceProvider` no longer calls `registerAtriumPlugin()`, `loadViewsFrom()` or `Blade::if('showroomCan')`, and no longer publishes the `showroom-views` tag. `ProductStatus::badge()` is removed. Translations, the other publish tags, `loadHistoryRoutes()` and the Impex integration stay.
13. No file under `src/`, `config/`, `resources/`, `workbench/` or `composer.json` contains the string `Atrium` (case-insensitive). A test greps for it. An arch rule asserts that no `RefactorCircus\Showroom` class uses `RefactorCircus\Atrium`, with no ignore list. The Impex arch rule's ignore list loses `TransferUiController`.
14. `composer.json` no longer has `refactor-circus/atrium` in `require-dev`, `suggest` or `conflict`. It also loses `extra.atrium`, the Atrium VCS repository, `refactor-circus/pennantplus` (`require-dev` and `suggest`, D-011) and the `test:without-atrium` script, which also leaves `test`. The Keen `suggest` text stops mentioning Atrium. `require` is still PHP, Laravel packages and `refactor-circus/keystone` only. `ComposerTest` asserts each point.
15. `config/showroom.php` has no `ui` or `atrium` section. A new `stats` banner section holds `cache_seconds` (default 60), read as `max(0, (int) config(...))`. Nothing reads `showroom.atrium.*` or `showroom.ui.*`. `showroom.impex.*` is unchanged.
16. `parityGaps()` checks the `http` and `mcp` surfaces only, and `PARITY_EXCEPTIONS` has no `ui` entries. The arch parity test is renamed to "every use case is reachable from the HTTP API and MCP".
17. `testbench.yaml`, `workbench/` and `.github/workflows/tests.yml` drop every Atrium and Pennant entry: the Atrium provider, the Pennant migrations, the `/atrium/...` start path, `atrium-assets`, `viewAtrium`, `impex.atrium.show_all`, the pennantplus store and the `without-atrium` CI job. `tests/TestCase.php` and `tests/Pest.php` no longer guard on or register Atrium. One exception: while a pre-v2 dev dependency (Cortex, Impex or Keen) still installs Atrium and the suite needs it, `tests/TestCase.php` alone may keep `atrium.disabled`, with a comment naming those packages' v2 specs (Risks).
18. `docs/agent/standards/showroom/atrium-screens.md` is deleted.

### OpenAPI and TypeScript client

19. `composer openapi:export` writes `openapi.json` at the package root with Scramble (`dedoc/scramble` in `require-dev` only), run against the Testbench workbench. Scramble is configured in the workbench: `api_path` is `showroom` and `info.version` is fixed, so the output is deterministic. The committed `openapi.json` lists every API route the package registers by default (`showroom.*` names in the API group) with its method and path. A test compares the route list with the document's paths.
20. In `openapi.json`, the 200 responses of `showroom.stats.show`, `showroom.search.index` and `showroom.settings-summary.show` carry object schemas with the required keys of their resource `schema()`. The `search` operation lists the `q`, `type` and `limit` query parameters.
21. `client/` is committed with these files:
    - `package.json`: name `@refactor-circus/showroom-client`, version `2.0.0`, `openapi-fetch` as a dependency, and `openapi-typescript` plus `typescript` as dev dependencies.
    - `src/schema.d.ts`: generated by `openapi-typescript` from `../openapi.json`. Its first line stamps the sha256 of the `openapi.json` it was built from.
    - `src/index.ts`: exports `createShowroomClient({ baseUrl, headers?, fetch?, credentials? })`, which returns openapi-fetch's typed `Client<paths>`, and re-exports `paths`, `components` and `operations`.
    - Built `dist/index.js`, `dist/index.d.ts` and `dist/schema.d.ts`, with `main`/`types`/`exports` pointing at `dist`.
    - `README.md` with a usage example.

    `client/node_modules` is git-ignored and excluded from the Composer dist.
22. `composer client:generate` runs `openapi:export`, then `npm --prefix client ci` and `npm --prefix client run generate`. The `generate` step runs openapi-typescript, the stamp and `tsc`. Running it on a clean checkout leaves `git status` clean.
23. `tests/Feature/OpenApiTest.php` fails when either artifact is stale. It exports the document to a temporary path and asserts that it equals the committed `openapi.json`. It also asserts that the hash stamped in `client/src/schema.d.ts` equals the committed `openapi.json`'s sha256, and that `client/dist/schema.d.ts` equals `client/src/schema.d.ts`. The test needs no Node. A CI job with Node runs `composer client:generate` and `git diff --exit-code`.

### Docs and release

24. The README describes Showroom as a JSON API + MCP package and has these sections:
    - "Front end: Atrium v2", on optional installation, with a pointer to Atrium's integration;
    - "OpenAPI and TypeScript client", covering regeneration and a git dependency example;
    - the three new endpoints under "HTTP API".

    The "Dashboard" and "Screen extensions" sections are removed. `docs/11-dashboard.md` is deleted, `docs/09-api.md` documents the new endpoints, and the other `docs/*.md` lose their Atrium screen references. `DocsTest` asserts each point and no longer asserts "Atrium is optional".
25. The CHANGELOG's next release is headed v2.0.0. Its upgrade note says:
    - the Atrium UI, `showroom.ui.*`, `showroom.atrium.*`, `ShowroomSupportFeature`, the `showroom-views` publish tag and `ProductStatus::badge()` are removed;
    - `showroom.atrium.widget_cache_seconds` becomes `showroom.stats.cache_seconds`;
    - install Atrium v2 for a UI;
    - the new endpoints, `openapi.json` and `client/` are added.
26. The Boost skill (`resources/boost/skills/showroom-development/SKILL.md`) and `CLAUDE.md` describe the package as API + MCP. They drop the Atrium screen conventions, name the Catalog domain, `client:generate` and the staleness test, and amend the "existing keys are never renamed or removed" rule to allow removal in a major release with an upgrade note (D-014).
27. `composer test` passes: analyse, lint, type coverage and unit.

## Design

### Catalog domain (`src/Domains/Catalog`, D-004)

Read-only, cross-domain views that the API did not cover. It owns no model. It reads other domains' models, as the deleted plugin did, and writes nothing.

- `CatalogServiceProvider` (listed in `Domains\DomainServiceProvider::$providers`) loads `routes.php` through `loadApiRoutesFrom()`:
  - `Route::get('stats', [CatalogController::class, 'stats'])->name('stats.show')`
  - `Route::get('search', [CatalogController::class, 'search'])->name('search.index')`
  - `Route::get('settings-summary', [CatalogController::class, 'settingsSummary'])->name('settings-summary.show')`
- `Http/Controllers/CatalogController`: thin, `return $request->persist();`.
- `Http/Requests/ShowCatalogStatsRequest`, `SearchCatalogRequest` and `ShowSettingsSummaryRequest` extend Keystone's `Request`. `authorize()` is `parent::authorize() && $this->allows('viewAny', ProductModel::class)` for stats and settings summary. For search, the caller must be allowed `viewAny` on at least one of the requested types' model classes (`SearchType::model()`).
- `Enums/SearchType`: a string-backed enum with the 7 values, in result order, and `model()`.
- `Data/SearchCatalogData` (`q`, `?SearchType $type`, `int $limit = 5`) with `rules()`. `Data/CatalogStats` is the final readonly result (`statusCounts`, `published`, `completeness`, `reviewQueue`, `reviewTotal`, `recentVersions`).
- Actions, each with a `{Subject}{Showing|Shown|Searching|Searched}ActionEvent` pair and no transaction (read only):
  - `ShowCatalogStatsAction::execute(): CatalogStats`. It moves the plugin's `statusCounts()`, `completenessRows()` (output key `channel`, was `scope`), review-queue and recent-versions queries, with `versionable` and `author` eager-loaded. Completeness goes through `Cache::remember('showroom:stats:completeness', $seconds, …)` when `$seconds > 0`.
  - `SearchCatalogAction::execute(SearchCatalogData $data, ?Authenticatable $user): Collection<SearchHit>`. It filters types with `Authorizer::for($package)->can($user, 'viewAny', $model)`, as the plugin's `mayFind()` did, and runs one query per allowed type with the plugin's matching and order, capped at `limit`.
  - `ShowSettingsSummaryAction::execute(): array`: the cast config summary.
- `Resources/CatalogStatsResource`, `CatalogSearchResultResource` and `SettingsSummaryResource` are final Keystone `JsonResource`s with `schema()`.
- MCP: `Mcp/Requests/ShowCatalogStatsMcpRequest` and `SearchCatalogMcpRequest`, `Mcp/Tools/ShowCatalogStatsTool` and `SearchCatalogTool`, registered in `ShowroomServer`. The settings summary is host configuration for an admin panel, so it gets no tool (D-006).

### Payloads (D-003)

```json
{"data": {
  "status": {"counts": {"draft": 3, "in_review": 1, "approved": 4, "archived": 0}, "published": 2},
  "completeness": [{"channel": "web", "locale": "en_GB", "products": 8, "complete": 5, "average": 81}],
  "review_queue": {"products": [{"id": "01J…", "identifier": "SKU-1", "slug": "sku-1", "status": "in_review", "updated_at": "…"}], "total": 1},
  "recent_versions": [{"version": 3, "action": "updated", "comment": null, "author": {"type": "user", "id": 1}, "changes": {}, "created_at": "…", "subject": {"type": "product", "id": "01J…", "key": "SKU-1"}}]
}}
```

Search: `{"data": [{"type": "owners", "id": "01J…", "code": "acme", "label": "Acme", "subtitle": "acme · vendor"}]}`. For products, `code` and `label` are the identifier and `subtitle` is null. Atrium builds its own links from `type` and `id`.

### Removal

- Delete `src/Atrium` whole, `resources/views`, the Atrium tests and fixtures, the without-Atrium lane and PennantPlus (only `ShowroomSupportFeature` used it).
- In `ShowroomServiceProvider`, drop the plugin, views, Blade directive and views publish tag.
- `ProductStatus` loses `badge()` and its `Badges` import.
- `config/showroom.php` loses `ui` and `atrium` (and the `ShowroomSupportFeature` import) and gains:

  ```php
  /*
  | Stats
  | `cache_seconds`: how long GET {prefix}/stats keeps its completeness
  | figures, which read every stored score. 0 reads them fresh.
  */
  'stats' => ['cache_seconds' => 60],
  ```
- `tests/Pest.php` drops the `trait_exists(ResetsScreenExtensions)` guard and the `ui` surface. `tests/TestCase.php` drops `AtriumServiceProvider` and `ResetsScreenExtensions`.

### OpenAPI and client (D-008, D-009)

- The workbench owns all Scramble configuration (`workbench/app/Providers/WorkbenchServiceProvider.php` or `workbench/config/scramble.php`). Any Scramble extension needed to describe `persist()` responses from a resource's `schema()` lives in `workbench/app/OpenApi/`. `src/` never names Scramble.
- Composer scripts:
  - `openapi:export`: `@php vendor/bin/testbench scramble:export --path=openapi.json`
  - `client:generate`: `@openapi:export`, `npm --prefix client ci`, `npm --prefix client run generate`
- `client/scripts/stamp.mjs` writes `// openapi.json sha256: <hash>` as the first line of `src/schema.d.ts`.
- The Pest staleness test runs the export in-process through the same workbench configuration (an `OpenApiTestCase` registering `ScrambleServiceProvider` and the workbench config). A new `client` CI job (Node LTS) regenerates and diffs.
- `.gitignore` gets `client/node_modules`, and `.gitattributes` gets `/client/node_modules export-ignore`.

### Departures from standards

- `actions/actions-events` says read actions have no events. Showroom's own arch rule requires a pair on every Action, so the read Actions keep the pair, as the existing list and show Actions do (package override).
- `CLAUDE.md`'s "never remove a config key" rule is broken on purpose for the major release (D-014). No shared standard is broken, so `standards-feedback.md` gets no row.

## Out of scope

- A transfers-list endpoint or any Showroom-side transfer record (D-001). Atrium reads Impex's runs.
- `showroom.impex.*`, `ImpexIntegration` and `src/Impex`, unchanged. Any cross-package reference there is for its own spec.
- Porting the screens, widgets, search UI or settings panel. That is Atrium v2's spec (`Atrium/2026-11-22-1823-atrium-v2-headless`).
- Pruning translation keys that only the deleted views used (D-012).
- Changing the Keystone constraint (D-010), `showroom.routes.enabled` (stays `true`, D-002), or any existing endpoint's behavior.
- Publishing the client to an npm registry. Atrium pins it as a git dependency.
- Tagging the v2.0.0 release (the package-release step, after merge).

## Risks and open questions

- **Transitive Atrium.** Cortex, Impex and Keen at `^0.1` require Atrium, so it stays installed in dev until their v2 specs land. If their providers need Atrium booted in tests, `tests/TestCase.php` keeps `atrium.disabled` (AC 17). The arch and grep rules still keep Showroom's own code clean.
- **Scramble inference.** Responses come from `$request->persist()`, which Scramble may not follow to the resource. The new endpoints must have full schemas (AC 20). Existing endpoints are guaranteed path, method, parameters and request body; their response schemas are best effort, through a workbench extension if needed. Gaps limit the client's typing of those responses, not its paths.
- **Determinism.** Scramble output must be byte-stable across runs and machines (key order, `info.version`, server URL). Otherwise the staleness test flakes. Pin the version in the workbench config and use a relative server URL.
- **Git dependency on a subfolder.** npm cannot install a git subdirectory; pnpm (`#v2.0.0&path:client`) and Yarn can. Consumers choose how to install it (Showroom's own Atrium module imports it from the repository); committing `dist/` keeps it usable without a build step.
- **Breaking for hosts.** Hosts that published the Showroom views, set `showroom.atrium.*`, used `ShowroomSupportFeature` or called `ProductStatus::badge()` break. The CHANGELOG upgrade note covers each.
- **Waits for** Keystone `2026-11-23-0943-drop-the-atrium-plugin-hook`. Removing the call works on the current Keystone too, so the build does not need a Keystone release.
