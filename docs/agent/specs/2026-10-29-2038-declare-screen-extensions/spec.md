# Declare screen extensions (Showroom)

## Goal

Every Showroom page in Atrium declares itself on Atrium's screen extensions registry (Atrium spec `2026-10-29-2037-screen-extensions-registry`, PR #16), so other packages add tabs and sections to it without Showroom knowing they exist. Showroom stops rendering other packages' UI inline (audit trails, the History nav item, the Impex run link) and stops requiring Atrium.

## Acceptance criteria

### Screens

1. **Every screen is declared.** Each controller view below builds its screen with `Screen::standard($key, $kind)` (`RefactorCircus\Atrium\Domains\Screens\Data\Screen`), passes it to `ScreenExtensions::resolve($screen, $request)` after its own `authorizeScreen()` / `abort_unless()` check, and passes the result to the view as `resolved`. None has built-in tabs, so each gets the synthetic `overview` tab, which is the default.

   | Key | Route name (`atrium.showroom.…`) | Kind | Record | Row record (`row.actions`) |
   |---|---|---|---|---|
   | `showroom.products` | `products.index` | list | none | `ProductModel` |
   | `showroom.product-create` | `products.create` | detail | none | |
   | `showroom.product` | `products.show` | detail | `ProductModel` | |
   | `showroom.product-models` | `product-models.index` | list | none | `ProductModelModel` |
   | `showroom.product-model` | `product-models.show` | detail | `ProductModelModel` | |
   | `showroom.channels` | `channels.index` | list | none | `ChannelModel` (channel rows only, not locale rows) |
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

   Proven by `tests/Feature/Ui/ScreenExtensionsTest.php`: a dataset over all 25 screens registers a tab and a section in every slot the screen declares, opens the page as an allowed user, and asserts each section renders once inside its slot (`data-testid="section-{key}"`), a section on an undeclared slot does not render, and the tab is in the tab bar and opens through `?tab=`. For list screens with at least two rows seeded, the row section renders once per row and its `authorize` closure receives each row as `$record`.
2. **Access closure.** Every screen gets `access(fn (string $permission, Request $request, ?Model $record): bool => ScreenAccess::allows($permission, $record ?? $subject, $request))`, using Showroom's own `Atrium\ScreenAccess` (the `showroom.policies` policies, through Keystone's `Authorizer`). `$subject` is the model class in the table's Record column, or the list's row class when the record is none; `showroom.transfers` uses `ProductModel::class`, as its own check does. A feature test, with `showroom.authorization` on, registers a `permission('view')` section on `showroom.product` and `showroom.products` and asserts it renders for a user the policy allows and not for one it denies; and a `permission('acme.extra')` section renders only once `Gate::define('acme.extra', …)` allows the user.
3. **Own authorization first.** A user the page refuses gets 403 and no registered `authorize` closure or resolver runs: a feature test on `showroom.product` and `showroom.products` with counting closures asserts zero calls.
4. **Layout.** Every view lays out the slots the same way (Atrium criterion 15):
   - `header.actions` in `x-atrium::page-header`'s `actions` slot, beside any existing header actions;
   - `x-atrium::screen-tabs` directly under the header;
   - when `$resolved->openRegisteredTab` is set, `x-atrium::screen-tab-panel` replaces the page body, and only `header.actions` stays;
   - otherwise `before` first in the body and `after` last; on detail screens `aside` in the page's side column when the page has one, else directly before `after`;
   - in tables, `row.actions` in a trailing cell of each row (header cell empty); on `showroom.assets` at the bottom of each tile.

   A feature test on `showroom.product` with a section in every slot asserts all render on the overview tab, and with a registered tab open only `header.actions` and the panel render (the other resolvers are not called).
5. **Nothing registered, nothing changes.** With nothing registered no tab bar and no extra markup render, and the existing feature suites pass. Existing tests may change only where this spec forces it (criteria 6 and 7); each changed or deleted test is listed in the PR by file and test name.
6. **Audit trails and History nav item removed.** No `x-atrium::audit-trail` remains in `resources/views` (the products index and the product, category, attribute, owner, product-model, asset, family and channel show pages), and `ShowroomPlugin::navigation()` no longer calls `historyNavItem()`. Proven by a feature test asserting no file under `resources/views` contains `x-atrium::audit` and no navigation item's URL points at `history`. `tests/Feature/HistoryNavigationTest.php` and `tests/Feature/Ui/AuditTrailTest.php` are deleted; the `tests/Keen` UI tests that assert `data-testid="audit-trail"` (`shows no history until an audit log is installed`, `shows a product its own history from the installed audit log`) are deleted. Keen's own spec covers its History section. The JSON `GET {prefix}/history` route and history MCP tool stay.
7. **Run link removed.** `resources/views/ui/transfers/index.blade.php` no longer names Impex: the flow cell always shows `$run->flow` as plain text, and the trailing `row.actions` cell renders the sections registered for the run. A feature test asserts the view file contains neither `Impex` nor `atrium.impex`, and that a section registered on `showroom.transfers` / `row.actions` renders once per run row with that `RunModel` as `$record`. The test `links a transfer to its Impex run only when Impex would open it for the viewer` in `TransferPagesTest.php` is deleted (Impex spec `register-showroom-run-link` takes it over).
8. **Test isolation.** `tests/TestCase.php` uses `RefactorCircus\Atrium\Testing\ResetsScreenExtensions`, so registrations made inside one test are gone in the next. Proven by the criterion 1 dataset passing under `--parallel` and in random order.

### Atrium becomes optional

9. **Composer.** `refactor-circus/atrium` moves from `require` to `require-dev` (`dev-main`) and gets a `suggest` entry ("Browse and manage the catalog from the Atrium dashboard (dev-main)."). Keystone is the only `refactor-circus/*` package left in `require`. A feature test reads `composer.json` and asserts both. `composer.local.json` is regenerated with `bin/composer-local.php`.
10. **Arch test.** `tests/ArchTest.php` gains `arch('code outside src/Atrium uses no Atrium class')->expect('RefactorCircus\Showroom')->ignoring('RefactorCircus\Showroom\Atrium')->not->toUse('RefactorCircus\Atrium')`, and it passes.
11. **No-Atrium proof.** A `NoAtrium` test suite (`tests/NoAtrium`, its own `NoAtriumTestCase` that loads Keystone and Showroom only, added to `phpunit.xml`) asserts: the provider boots; no `atrium.showroom.*` route exists; one JSON API read and one write succeed (`showroom.products.index` and a product create); one MCP tool call succeeds. Each test skips when `RefactorCircus\Atrium\Atrium` exists, so the default run skips it. A `no-atrium` job in `.github/workflows/tests.yml` (ubuntu, PHP 8.5, prefer-stable) runs `composer remove --dev refactor-circus/atrium refactor-circus/cortex refactor-circus/impex --no-update`, `composer update`, then `vendor/bin/pest --testsuite=NoAtrium --fail-on-skipped`. Cortex and Impex go too because both require Atrium. Done-step: the builder runs the same commands in a scratch copy and pastes the output into the PR.

### Documentation

12. The README's Atrium/history paragraph (line 304), the Boost skill (`resources/boost/skills/showroom-development/SKILL.md:40`), `CLAUDE.md` and the local standard `docs/agent/standards/showroom/atrium-screens.md` drop the audit-trail text. The standard's "Audit trail" section becomes "Screen declarations": every screen is declared with `Screen::standard()` and the keys in criterion 1, history comes from Keen by wildcard, and Atrium is suggested. The README lists the 25 keys and their slots, says Atrium is optional, and says that `permission()` extensions on Showroom screens follow `showroom.authorization`. Done-step: a grep for `audit-trail`, `historyNavItem` and `atrium.impex` in `src`, `resources`, `docs`, README and CLAUDE.md is empty and listed in the PR.
13. **Gate.** `composer test` passes (`analyse`, `lint:check`, `test:types`, `test:unit`), including `StylesTest` (`AtriumStyles::missingClasses()` and `inlineStyles()` empty). Showroom has no browser suite.

## Design

- **`RefactorCircus\Showroom\Atrium\Support\Screens`** (final, static only): `detail(string $key, ?Model $record, string $subject): Screen` and `list(string $key, string $subject): Screen` (`$subject` is a `class-string<Model>`). Each returns `Screen::standard($key, Screen::DETAIL|Screen::LIST)->record($record)->access(…)` with the criterion 2 closure. Controllers call `app(ScreenExtensions::class)->resolve(Screens::detail(…), $request)` after their existing check.
- **Views** pass the `resolved` variable to `x-atrium::screen-tabs`, `screen-tab-panel` and `screen-sections`, laid out per criterion 4. They wrap no slot in anything except the existing grid column, which is how D-001's placement (for example `lg:col-span-3` on assets) is kept.
- **History** comes from Keen: its config attaches a History section by wildcard (default slot `after`). Showroom declares no audit subject.
- **Run link**: Impex registers it on `showroom.transfers` / `row.actions` from its own config. Showroom names no package.
- **Without Atrium**: `registerAtriumPlugin()` already skips the plugin. `Blade::if('showroomCan', …)` and `loadViewsFrom()` stay where they are: they load no Atrium class until a view renders, and the NoAtrium suite checks that.
- No config, migrations, routes, events, actions or MCP changes.

## Out of scope

- Section B: gating the transfers page and nav on `ImpexIntegration::active()`, the Impex flows integration, and the Scout search fallback.
- Registering anything into other packages' screens.
- Keen's History section and Impex's run-link section (their own specs).
- Built-in tabs for any Showroom page.

## Risks and open questions

- **Merge grouping.** Removing `x-atrium::audit-trail` and `historyNavItem()` is forced by Atrium PR #16, so this merges with Atrium, Keen and Impex as a group.
- **Q1: `permission()` with `showroom.authorization` off.** `ScreenAccess::allows()` answers `true` for everything when authorization is off, so every `permission()` extension shows on Showroom screens, including another package's ability. Options: (a) keep it: one rule for every Showroom control, documented in the README, and registrants who need their own check use `authorize()`; (b) ask the Gate directly in the access closure, ignoring `showroom.authorization`. Recommendation: (a), because the suite rule is that the access closure uses the package's own authorization.
- **Q2: transfers row slot placement.** The design puts `row.actions` in a trailing cell, so with Impex's link section installed the flow name shows twice: as text in the flow cell and as a link in the trailing cell. Options: (a) trailing cell, the same as every other list (recommended; Impex's section can render "Open run" instead of the name); (b) the flow cell renders the row sections in place of the plain name when any is visible. Option (b) gives the slot a meaning only Showroom knows.
- **Screen count.** The shape said "about 20"; there are 25 controller views, including `showroom.family-variant`, which the shape's list did not name. All 25 are declared because every Atrium screen is.
- The no-Atrium lane also removes Cortex and Impex, so it proves Showroom's core without either. Showroom's `src/Impex` integration already guards on `ImpexIntegration::active()`.
