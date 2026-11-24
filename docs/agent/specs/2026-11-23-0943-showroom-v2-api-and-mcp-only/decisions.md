# Decisions

## D-001: Should Showroom add a transfers-list endpoint (`GET showroom/transfers`)?
- Choice: No. Showroom keeps no transfer record of its own: runs are Impex's `RunModel`, which `TransferUiController` read directly. Showroom adds no endpoint and no record. Atrium lists Showroom's runs by calling Impex's runs API (`GET {impex prefix}/runs`, filtered by `flow`) in its own integration. Runs are still started through `POST showroom/imports` and `POST showroom/exports`. The export form's choices (families, channels, root categories) come from the existing list endpoints.
- Rationale: Adds no cross-package code and no new Showroom state, and keeps Impex the one owner of run records. Impex's `flow` filter is an exact match today, so Atrium needs either one call per `showroom:*` flow or a prefix filter in Impex. That is for the Impex or Atrium spec, not this one.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-002: Does the package's API stay on by default (`showroom.routes.enabled`)?
- Choice: Unchanged: `showroom.routes.enabled` stays `true`.
- Rationale: The most conservative choice: no behavior change for existing hosts, and the API becomes the package's only HTTP surface.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-003: What exactly does `GET showroom/stats` return?
- Choice: One payload with four sections that carry the widgets' figures unchanged:
  - `status {counts, published}`;
  - `completeness [{channel, locale, products, complete, average}]`, where the widget's `scope` key becomes `channel`;
  - `review_queue {products (oldest 5, {id, identifier, slug, status, updated_at}), total}`;
  - `recent_versions` (the newest 8, `VersionSummaryResource` fields plus `subject {type, id, key}`).

  Only `completeness` is cached.
- Rationale: A transport-only move of the four widgets' resolvers, matching Atrium v2's "transport-only" rule. Small summaries avoid shipping full product resources in a dashboard call.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-004: Where do the new endpoints live?
- Choice: A new read-only `src/Domains/Catalog` domain (`CatalogServiceProvider`, `routes.php`, `CatalogController`, three Requests, Actions and Resources, two MCP tools).
- Rationale: Each endpoint reads several domains: products, workflow, channels, and seven searchable record types. One domain module follows `architecture/domain-modules`, keeps the per-domain `routes.php` pattern and avoids spreading one feature across the Product, Workflow and Search domains. The existing `Search` domain is the product search engine and stays as it is.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-005: How do stats, search and settings summary authorize?
- Choice:
  - Stats: `viewAny` on products, as all four widgets did.
  - Search: `viewAny` per record type, as the plugin's `mayFind()` did. It answers 403 when the caller may list none of the requested types.
  - Settings summary: `viewAny` on products.
  - Guests get 401 while `showroom.authorization` is on, and with authorization off everything is allowed.
- Rationale: Keeps the existing access rules. The settings panel had no Showroom ability (Atrium's gate guarded it), and Showroom cannot name Atrium's gate. Requiring a signed-in catalog reader is the most restrictive check available through `showroom.policies` without adding a new ability.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-006: Do the new Actions get MCP tools (parity)?
- Choice: Yes for stats (`show-catalog-stats-tool`) and search (`search-catalog-tool`). `ShowSettingsSummaryAction` gets a declared `mcp` exception in `PARITY_EXCEPTIONS`.
- Rationale: Parity is the package default, and stats and search are useful to agents. The settings summary describes host configuration for an admin panel and adds nothing for an MCP client, so the exception is declared with its reason as the parity rule requires.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-007: Search semantics (matching, limits, `type`)?
- Choice: Matching and ordering are the same as the deleted Atrium search. `q` is required (at most 191 characters), `type` optionally takes one of the 7 types, and `limit` runs 1 to 20 with a default of 5 per type. Results are a flat list in the fixed type order, each `{type, id, code, label, subtitle}`. Showroom returns no links; Atrium builds them.
- Rationale: A transport-only move. Showroom must not know Atrium's paths, and `limit` only lets a type-filtered search show more than the ⌘K default.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-008: How does a test detect a stale `openapi.json` or `client/` without requiring Node in `composer test`?
- Choice: The Pest test re-exports the OpenAPI document in-process and compares it with the committed `openapi.json`. The client is checked through a sha256 stamp of `openapi.json` on the first line of `client/src/schema.d.ts` (and `dist/schema.d.ts` equal to it). A separate CI job with Node runs `composer client:generate` and `git diff --exit-code`.
- Rationale: Keeps the PHP suite runnable everywhere and still fails on either artifact going stale. CI catches drift in the generated TypeScript itself.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-009: What does `client/` ship?
- Choice: TypeScript source plus a committed `dist/` built with `tsc`. `package.json` names `@refactor-circus/showroom-client` at version `2.0.0`, with `main`/`types`/`exports` pointing at `dist`. It is not published to a registry.
- Rationale: A git dependency is installed without a build step, so consumers (originally planned as Atrium's Nuxt layer; now any JS consumer outside the repository) need compiled output. Publishing to a registry is not part of the shaped decision.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-010: Does Showroom bump its Keystone constraint for the drop-the-atrium-plugin-hook release?
- Choice: No. The `refactor-circus/keystone` constraint stays as it is.
- Rationale: Showroom only stops calling `registerAtriumPlugin()`, which works on the current and the next Keystone alike. The Keystone version question is still open in its own spec.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-011: Does `refactor-circus/pennantplus` stay a dev dependency?
- Choice: No. It is removed from `require-dev` and `suggest`, together with `tests/PennantPlus`, `PennantPlusTestCase`, the workbench pennantplus store and the Pennant migrations in `testbench.yaml`.
- Rationale: Its only use was `ShowroomSupportFeature`, which the shape moves to Atrium (`atrium.integrations.showroom.*`).
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-012: Are translation keys used only by the deleted views pruned?
- Choice: No. `lang/en/showroom.php` and `loadTranslationsFrom()` stay unchanged; pruning is a follow-up.
- Rationale: Not in the shaped scope. Leftover keys are harmless, while a pruning pass risks removing keys that API and MCP messages build dynamically.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-013: Do the package-to-package references flagged in shaping (`showroom.impex.*`, `ImpexIntegration`) become their own specs here?
- Choice: No. They stay out of scope and unchanged, and this spec opens no new spec. A later shaping pass can raise them.
- Rationale: The user's instruction keeps them out of scope. A spec changes only its own package.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-014: May config keys be removed, given CLAUDE.md's "existing keys are never renamed or removed"?
- Choice: Yes, for this major release. `showroom.ui.*` and `showroom.atrium.*` are removed, and `widget_cache_seconds` moves to `showroom.stats.cache_seconds`, all listed in the CHANGELOG upgrade note. `CLAUDE.md` is amended to allow removal in a major release with an upgrade note. `ProductStatus::badge()` and the `showroom-views` publish tag go the same way.
- Rationale: The shape decides a breaking v2.0.0. The rule is a local convention, not a shared standard, so no standards-feedback row is needed.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-015: Spec review
- Choice: Skipped. The spec moves from written straight to `approved`.
- Rationale: The user asked for the spec and tasks to be written and approved without a review round or questions.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-016: Where are group 1's four out-of-list test edits committed?
- Choice: Committed as a follow-up to group 1 (tool count 83 to 84 in McpTest, OutputSchemaTest and CortexTest; `ShowCatalogStatsAction => ['ui']` parity exception in tests/Pest.php, which group 3 removes with the ui surface).
- Rationale: The group's own tests need them; recommended option.
- Date: 2026-11-23
- Raised by: factory build (group 1); default chosen because the user asked to proceed without stopping

## D-017: Where do group 2's four out-of-list files get committed?
- Choice: Committed as a group 2 follow-up, as D-016 did for group 1 (SearchHit.php and the 84 to 85 tool counts).
- Rationale: Recommended option.
- Date: 2026-11-23
- Raised by: factory build; default chosen because the user asked to proceed without stopping

## D-018: How is the phpunit.xml.dist OpenApi testsuite committed?
- Choice: Committed as a group 5 follow-up, as D-016 and D-017 did, so vendor/bin/pest runs tests/OpenApi.
- Rationale: Recommended option.
- Date: 2026-11-23
- Raised by: factory build; default chosen because the user asked to proceed without stopping

## D-019: Which version does the suite release under?
- Choice: Every suite package releases as v2.0.0 to match: Keystone, Atrium, Cortex, Impex, Keen, PennantPlus, Polycart, Roster and Showroom. Every `refactor-circus/*` constraint (require and require-dev) is `^2.0`, and every repo aliases `dev-main` to `2.0.x-dev`. Foundry and Guardian stay on Keystone `^0.1` until they migrate.
- Rationale: The user said "i'd like everything release as v2 to match". This supersedes any earlier choice to keep `^0.1` constraints or release Keystone as v0.2.0.
- Date: 2026-11-23
- Raised by: user

## D-020: Where does Showroom's Atrium front end live?
- Choice: In Showroom. The Vue module staged in Atrium (`ui/modules/showroom`) moves to `atrium/`, built with `@refactor-circus/atrium/vite` into the committed `dist/atrium/` and declared in `composer.json` under `extra.atrium`; Atrium's Blade page injects its script. Types come from Showroom's own `client/src/schema.d.ts`; the transfers page keeps a copy of Impex's schema as `atrium/types/impex-schema.d.ts` (no dependency on Impex). The PHP stays Atrium-free: `NoAtriumReferencesTest` and the ArchTest rule are narrowed to allow only `extra.atrium`, `atrium/`, `dist/atrium/`, the `ui` CI job and docs. `AtriumModuleTest` checks the declaration and the build's source hash.
- Rationale: The user said "move the vue files for each module back into their respective modules. modules need to register their js. js should be injected into the blade file. modules are responsible for their own frontend and cjs clients from now on".
- Date: 2026-11-24
- Raised by: user

## D-021: Who declares Showroom's Atrium integration?
- Choice: Showroom, in a static JSON manifest. `atrium/src/integration.json` (Atrium's integration schema, version 1, key `showroom`) declares the `showroom.atrium` feature, the eleven nav items, the four widgets, the settings panel, the search source and the 27 screens; the transfers screen's `flow_prefix` is a `$config` expression on `atrium.integrations.showroom.transfers.flow_prefix` (default `showroom:`). The preset validates it and writes `dist/atrium/integration.json`, which `extra.atrium.integration` names. The SDK pin moves to Atrium 009b5bc (D-090 there). No PHP and no Atrium dependency are added; `AtriumModuleTest` checks the declaration points at a manifest with key `showroom` and version 1 that matches its source, and `atrium/test/integration.test.ts` checks the module registers everything the manifest declares.
- Rationale: The user said "move the php declarations": each package's Atrium integration moves out of Atrium into the package, and Atrium reads it from the package's JSON manifest.
- Date: 2026-11-24
- Raised by: user

## D-022: Is Showroom's Atrium module built from Nuxt UI?
- Choice:
  - **Every page and component composes Nuxt UI 4 and the SDK's `Atrium*` components**, as Atrium's own do (Atrium D-091). The 19 tables are `UTable`s (TanStack column defs, cell slots, numeric columns right-aligned, the empty state in the `#empty` slot); selects are `USelect`s, whose "all", "none" and "choose" items carry `null` (Reka refuses an empty-string item), with models typed `string | null` and payloads mapping `null` as they mapped `''`; checkboxes are `UCheckbox`, the textarea `UTextarea`, file and number fields `UInput` (`type="file"` read and reset through its `inputRef`; `type="number"` keeps the forms' string values); `.atrium-empty` blocks are `AtriumState`, `.atrium-querybar` rows plain flex rows, page headings `AtriumPageHeader` (or `UPageHeader` with a `#title` slot where a test reads the title by its own `data-testid`), the category trail a `UBreadcrumb`, and `RouterLink`s `ULink`s. Routes, API calls, i18n and `data-testid`s are kept; native `<form @submit.prevent>` stays.
  - **A `UTable` row cannot carry attributes**, so each row's `data-testid` sits on its first cell's content and tests climb to the row with `row()` from `atrium/test/ui.ts`, which also drives Nuxt UI fields through their `v-model` (`pick()`, `picked()`, `choices()`, `isDisabled()`, `hasField()`): a `USelect` or `UCheckbox` renders a button and opens its menu in a portal, so `setValue()` cannot reach it.
  - **Enforced** by `atrium/test/nuxt-ui-guard.test.ts`, Atrium's guard rules over `atrium/src/**/*.vue` (no `<button>`, `<input>`, `<select>`, `<textarea>`, `<table>`, `<details>` or `<a href>`), with its self-test of the detector. The SDK pin moves to Atrium f6d6f83.
- Rationale: The user asked to "convert raw html": every Atrium UI is built on Nuxt UI, and Atrium's components already are (D-091 there), so the module's hand-styled tables, native selects and checkboxes were the remaining bespoke markup and kept the deck's look out of its pages.
- Date: 2026-11-24
- Raised by: user; choices made by the implementing agent without a user available
