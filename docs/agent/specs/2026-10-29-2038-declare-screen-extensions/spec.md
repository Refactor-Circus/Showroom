# Declare screen extensions (Showroom)

## Goal

Every Showroom page in Atrium declares itself on Atrium's screen extensions registry (Atrium spec `2026-10-29-2037-screen-extensions-registry`, PR #16), so other packages add tabs and sections to it without Showroom knowing they exist. Showroom stops rendering other packages' UI inline (audit trails, the History nav item, the Impex run link) and stops requiring Atrium.

## Acceptance criteria

### Screens

1. **Every screen is declared.** Each controller view below builds its screen through `Screens` (Design), resolves it with `ScreenExtensions::resolve($screen, $request)` after its own `authorizeScreen()` / `abort_unless()` check, and passes the result to the view as `resolved`. None has built-in tabs, so each gets the synthetic `overview` tab as its default.

   | Key | Route name (`atrium.showroom.…`) | Kind | Record | Row record (`row.actions`) |
   |---|---|---|---|---|
   | `showroom.products` | `products.index` | list | none | `ProductModel` |
   | `showroom.product-create` | `products.create` | detail | none | |
   | `showroom.product` | `products.show` | detail | `ProductModel` | |
   | `showroom.product-models` | `product-models.index` | list | none | `ProductModelModel` |
   | `showroom.product-model` | `product-models.show` | detail | `ProductModelModel` | |
   | `showroom.channels` | `channels.index` | list | none | `ChannelModel` (channel rows only) |
   | `showroom.channel` | `channels.show` | detail | `ChannelModel` | |
   | `showroom.assets` | `assets.index` | list | none | `AssetModel` (per tile) |
   | `showroom.asset` | `assets.show` | detail | `AssetModel` | |
   | `showroom.categories` | `categories.index` | list | none | `CategoryModel` (per tree row) |
   | `showroom.category` | `categories.show` | detail | `CategoryModel` | |
   | `showroom.owner-types` | `owner-types.index` | list | none | `OwnerTypeModel` |
   | `showroom.owner-type` | `owner-types.show` | detail | `OwnerTypeModel` | |
   | `showroom.owners` | `owners.index` | list | none | `OwnerModel` |
   | `showroom.owner` | `owners.show` | detail | `OwnerModel` | |
   | `showroom.families` | `families.index` | list | none | `FamilyModel` |
   | `showroom.family` | `families.show` | detail | `FamilyModel` | |
   | `showroom.family-variant` | `family-variants.show` | detail | `FamilyVariantModel` | |
   | `showroom.attributes` | `attributes.index` | list | none | `AttributeModel` |
   | `showroom.attribute-create` | `attributes.create` | detail | none | |
   | `showroom.attribute` | `attributes.show` | detail | `AttributeModel` | |
   | `showroom.attribute-groups` | `attribute-groups.index` | list | none | `AttributeGroupModel` |
   | `showroom.attribute-group` | `attribute-groups.show` | detail | `AttributeGroupModel` | |
   | `showroom.association-types` | `association-types.index` | list | none | `AssociationTypeModel` |
   | `showroom.transfers` | `transfers.index` | list | none | Impex `RunModel` (per run row) |

   Slots come from `Screen::standard()`: list = `header.actions`, `before`, `after`, `row.actions`; detail = `header.actions`, `before`, `after`, `aside`. No `history` slot. Proven by `tests/Feature/Ui/ScreenExtensionsTest.php`, a dataset over all 25 screens: it registers a tab and a section in every declared slot and asserts each section renders once (`data-testid="section-{key}"`), a section on an undeclared slot does not, `overview` is active without `?tab=`, and the registered tab opens through `?tab=`. On list screens with two rows, the row section renders once per row and its `authorize` closure receives each row as `$record`.
2. **Access closure.** Every screen gets `access(fn (string $permission, Request $request, ?Model $record): bool => ScreenAccess::allows($permission, $target, $request))`, using Showroom's own `Atrium\ScreenAccess`. `$target` is the record or row when its class is covered by `showroom.policies`, otherwise `$subject`: the Record column's class, or the row class for lists (`ProductModel::class` for `showroom.transfers`, as its own check does). So the Impex `RunModel` never reaches the Authorizer. With `showroom.authorization` off, every `permission()` extension shows (D-022). Feature tests, with authorization on: a `permission('view')` section on `showroom.product` and `showroom.products` renders for an allowed user and not a denied one; a `permission('acme.extra')` section renders only once the Gate allows it; a `permission('view')` row section on `showroom.transfers` renders for an allowed user, not a denied one, and throws nothing. With authorization off, the `showroom.product` section renders for anyone.
3. **Own authorization first.** A dataset over all 25 screens with counting closures asserts a denied signed-in user gets 403 and a guest 401, and no registered `authorize` closure or resolver runs.
4. **Layout.** `header.actions` in `x-atrium::page-header`'s `actions` slot; `x-atrium::screen-tabs` under the header; with a registered tab open, `x-atrium::screen-tab-panel` replaces the body and only `header.actions` stays; otherwise `before` first, the body, `aside` (in the side column when the page has one, else directly before `after`), `after` last. `row.actions` sits in a trailing table cell under an empty header cell (D-023: also on `showroom.transfers`, after the plain flow name), and at the bottom of each tile on `showroom.assets`, keeping its `lg:col-span-3` column (D-001). Proven by `assertSeeInOrder` on testids over every screen, plus the trailing-cell, tile and side-column checks.
5. **Nothing registered, nothing changes.** With nothing registered, a dataset over all 25 screens asserts no `screen-tabs`, `section-` or panel testids, and the list screens issue the same number of queries as before (`tests/Feature/PerformanceTest.php`). Existing tests change only where criteria 6 and 7 force it; each changed or deleted test is listed in the PR by file and test name.
6. **Audit trails and History nav item removed.** No `x-atrium::audit-trail` remains in `resources/views`, and `ShowroomPlugin::navigation()` no longer calls `historyNavItem()`. A feature test asserts no file under `resources/views` contains `x-atrium::audit` and no navigation item's URL points at `history`. Deleted: `tests/Feature/HistoryNavigationTest.php`, `tests/Feature/Ui/AuditTrailTest.php`, and the `tests/Keen` UI tests asserting `data-testid="audit-trail"`. The JSON `history` route and MCP tool stay, with their tests.
7. **Run link removed.** `resources/views/ui/transfers/index.blade.php` no longer names Impex: the flow cell shows `$run->flow` as plain text. A feature test asserts the view contains neither `Impex` nor `atrium.impex`, and a section on `showroom.transfers` / `row.actions` renders once per run row with that `RunModel` as `$record`. `TransferPagesTest`'s `links a transfer to its Impex run only when Impex would open it for the viewer` is deleted (Impex spec `register-showroom-run-link` takes it over).
8. **Test isolation.** `tests/TestCase.php` uses `RefactorCircus\Atrium\Testing\ResetsScreenExtensions`. Two ordered tests in one file prove it: the first registers a tab and a section on `showroom.product`, the second asserts both are absent.

### Atrium becomes optional

9. **Composer.** `refactor-circus/atrium` moves from `require` to `require-dev` (`dev-main`) and `suggest` ("Browse and manage the catalog from the Atrium dashboard (dev-main)."), and `conflict` gains `refactor-circus/atrium` below the first Atrium release with the screen registry (D-020). Keystone is the only `refactor-circus/*` package in `require`. A feature test reads `composer.json` and asserts all three. `composer.local.json` is regenerated with `bin/composer-local.php`.
10. **Arch test.** `tests/ArchTest.php` gains `arch('code outside src/Atrium uses no Atrium class')->expect('RefactorCircus\Showroom')->ignoring('RefactorCircus\Showroom\Atrium')->not->toUse('RefactorCircus\Atrium')`, and it passes.
11. **Without-Atrium proof (D-020).** A `WithoutAtrium` suite (`phpunit.without-atrium.xml`, bootstrap `tests/WithoutAtrium/bootstrap.php` removing the `RefactorCircus\Atrium\` PSR-4 prefix, a test case registering no Atrium provider) asserts: no Atrium class loads; the provider boots; no `atrium.` route exists; `showroom.products.index` and a product create succeed; `list-products-tool` answers and `tools/list` succeeds. The transfer tools and JSON routes register only when `ImpexIntegration::active()`; guard them if they do not. A `test:without-atrium` composer script runs it, and `composer test` calls it after `@test:unit`. A `without-atrium` job in `.github/workflows/tests.yml` runs `composer remove --dev refactor-circus/atrium refactor-circus/cortex refactor-circus/impex refactor-circus/keen --no-update` (each requires Atrium), `composer update`, then `composer test:without-atrium`.

### Documentation

12. **Docs.** The README, the Boost skill (`resources/boost/skills/showroom-development/SKILL.md`), `CLAUDE.md` and `docs/agent/standards/showroom/atrium-screens.md` drop the audit-trail text; the standard's "Audit trail" section becomes "Screen declarations". The README lists the 25 keys and their slots, says Atrium is optional, that `permission()` extensions follow `showroom.authorization` (registrants needing their own check use `authorize()`), and that row closures run once per row and must not query. The changelog says: install Atrium explicitly to keep the dashboard, re-publish published views, install Keen for History. A docs feature test asserts the README names the 25 keys and both rules, and that no file in `src`, `resources`, `README.md`, `CLAUDE.md` or `docs/agent/standards` contains `audit-trail`, `historyNavItem` or `atrium.impex`.
13. **Gate.** `composer test` passes, including `StylesTest` and the WithoutAtrium suite. Showroom has no browser suite.

## Design

- **`RefactorCircus\Showroom\Atrium\Support\Screens`** (final, static): `detail(string $key, ?Model $record, string $subject): Screen` and `list(string $key, string $subject): Screen` (`$subject` a `class-string<Model>`), each `Screen::standard(...)->record(...)->access(...)` with the criterion 2 closure.
- **Views** pass `resolved` to `x-atrium::screen-tabs`, `screen-tab-panel` and `screen-sections`, laid out per criterion 4, wrapping no slot in anything except the existing grid column (D-001).
- **History** comes from Keen's wildcard registration (default slot `after`). **Run link**: Impex registers it on `showroom.transfers` / `row.actions` from its own config; its section renders "Open run" (D-023).
- **Without Atrium**: `registerAtriumPlugin()` already skips the plugin; `Blade::if('showroomCan')` and `loadViewsFrom()` load no Atrium class.
- No config, migrations, routes, events or actions change, so the malformed-config rule (D-021) has nothing to apply to here.

## Out of scope

- Section B: gating the transfers page and nav on `ImpexIntegration::active()`, the Impex flows integration, the Scout search fallback.
- Registering anything into other packages' screens; Keen's History section; Impex's run-link section.
- Built-in tabs, new screens, routes or config.
- The JSON `history` route and history MCP tool.

## Risks

- **Merge grouping.** The removals are forced by Atrium PR #16, so this merges with Atrium, Keen and Impex as a group.
- **Conflict version.** Atrium has no tags yet; the `conflict` constraint is set from the first Atrium release carrying the registry when the group merges.
- **Screen count.** The shape said "about 20"; there are 25 controller views, all declared.

## Changelog

- Applied D-020 (WithoutAtrium suite in `composer test` plus CI lane; `conflict` on old Atrium), D-021 (noted: no integration config), D-022/D-024/D-026 (`permission()` follows `showroom.authorization`, documented), D-023/D-025/D-027 (`row.actions` trailing cell on transfers).
