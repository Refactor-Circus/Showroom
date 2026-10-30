# Build

## Group 2

Branch `ring-master/2026-10-29-2038-declare-screen-extensions--g2`, commit "2026-10-29-2038-declare-screen-extensions: group 2 Atrium optional". Criteria 9, 10, 11.

### Files

- `composer.json`: `refactor-circus/atrium` moved from `require` to `require-dev` (`dev-main`) and `suggest` ("Browse and manage the catalog from the Atrium dashboard (dev-main)."). Added `conflict` with `"refactor-circus/atrium": "<0.1.0"`. Added a `test:without-atrium` script (`vendor/bin/pest --configuration=phpunit.without-atrium.xml`), which `composer test` runs after `@test:unit`.
- `composer.local.json` (git-ignored): the same changes, copied by hand into this worktree and into the job worktree (`../Showroom--2026-10-29-2038-declare-screen-extensions/composer.local.json`, which points Atrium at `../Atrium--2026-10-29-2037-screen-extensions-registry`). Not regenerated with `bin/composer-local.php`, and no `composer update` was run.
- `tests/ArchTest.php`: `arch('code outside src/Atrium uses no Atrium class')`.
- `tests/Feature/ComposerTest.php`: Keystone is the only `refactor-circus/*` package in `require`. Atrium is in `require-dev` and `suggest` with the exact text. `conflict` has a `<` constraint on Atrium. `test:without-atrium` exists and runs after `@test:unit` in `test`.
- `phpunit.without-atrium.xml`: the `WithoutAtrium` suite, using bootstrap `tests/WithoutAtrium/bootstrap.php`.
- `tests/WithoutAtrium/bootstrap.php`: loads Composer's autoloader, sets the `RefactorCircus\Atrium\` PSR-4 prefix to no directories, and maps any class-map entries under that prefix to `false`.
- `tests/WithoutAtrium/WithoutAtriumTestCase.php`: registers only the `McpServiceProvider` and `ShowroomServiceProvider` providers, with no Atrium, Impex, Cortex or Keen.
- `tests/WithoutAtrium/WithoutAtriumTest.php`: checks that:
  - no Atrium class can load, and none is declared after boot, a JSON request or an MCP call;
  - the provider boots;
  - no route has an `atrium.` name or an `atrium` URI;
  - `showroom.imports.store` and `showroom.exports.store` are absent;
  - `showroom.products.store` and `showroom.products.index` work;
  - `ShowroomServer::tools()` (`tools/list`) succeeds and `list-products-tool` answers;
  - tool search does not list `start-import-tool` or `start-export-tool`, and calling them through `execute_tools` errors.
- `src/Mcp/ShowroomServer.php`: `createContext()` drops `StartImportTool` and `StartExportTool` (private `TRANSFER_TOOLS`) from the tool search catalog unless `ImpexIntegration::active()`. Before this, they were offered unguarded, and listing them calls `RunResource::structuredSchema()`, an Impex class.
- `src/Domains/Transfer/TransferServiceProvider.php` (**not in the group's file list**): loads `routes.php` (`imports.store`, `exports.store`) only when `ImpexIntegration::active()`. Routes are loaded in this provider, so this was the only place to guard them.
- `.github/workflows/tests.yml`: new `without-atrium` job. It runs `composer remove --dev refactor-circus/atrium refactor-circus/cortex refactor-circus/impex refactor-circus/keen --no-update`, then `composer update`, checks that `vendor/refactor-circus/atrium` is absent, then runs `composer test:without-atrium`.
- `src/ShowroomServiceProvider.php`: unchanged. `registerAtriumPlugin()` already skips when the Atrium class is missing, and `Blade::if('showroomCan', …)` and `loadViewsFrom()` load no Atrium class.

### Tests

The suite was not run here (the gate runs it). `vendor/bin/pint --test` passes on every changed PHP file, and `composer validate` passes. Both `composer.local.json` files parse as valid JSON.

### Deviations and notes

- **`composer.local.json` copied by hand, not regenerated.** Criterion 9 says to regenerate it with `bin/composer-local.php`. The job instructions said to copy the changes by hand instead, so the job worktree keeps its path repository pointing at the Atrium registry worktree.
- **`conflict` version.** Atrium has no tags. The changelog's first release heading is `v0.1.0`, and that release will be the first to carry the registry, so the entry is `<0.1.0`. A version compare does not match the `dev-main` branch, so this conflict does not block the current dev setup. Per the spec's risk note, adjust it when the group merges if Atrium tags differently. The test asserts only a `<` constraint, so the number can change without a test edit.
- **Transfer routes now 404 when Impex is inactive at boot.** Before, they answered `ImpexMissingException`'s 409. `docs/12-impex.md` line 19 still says "Without Impex, the start endpoints and tools answer `501`". That file is not in any group's list; group 3 or a follow-up should correct it. The existing `ImportExportTest` "says so when Impex is switched off" still passes, because it turns Impex off after boot, when the routes already exist.
- **Cortex is not covered by the tool guard.** Cortex reads `ShowroomServer::TOOLS` directly (Keystone `CortexIntegration::allTools()`), so the server-level filter does not apply there. Cortex requires Atrium, so a Showroom without Atrium has no Cortex. Cortex installed without Impex is outside this spec.
- `phpunit.without-atrium.xml` is not `export-ignore`d in `.gitattributes`, which is outside the group's files.

## Group 1

Branch `ring-master/2026-10-29-2038-declare-screen-extensions`, commit "2026-10-29-2038-declare-screen-extensions: group 1 Screen declarations". Criteria 1 to 8. This continues the salvaged commit 6e666ad (branch `…--g1`), which was cherry-picked onto the job branch first (commit "carry over salvaged work"). That commit holds the code: the views, controllers, `Screens`, the plugin change and `tests/TestCase.php`. This round added the tests and removed the forced ones.

### Files

- `src/Atrium/Support/Screens.php` (new): `detail(string $key, ?Model $record, string $subject)` and `list(string $key, string $subject)`. Each builds `Screen::standard(...)->record(...)->access(...)`. The access closure asks `ScreenAccess::allows($permission, $target, $request)`. `$target` is the record or row when `showroom.policies` covers its class, and `$subject` otherwise.
- `src/Atrium/Http/Controllers/*UiController.php`: each of the 25 views resolves its screen with `ScreenExtensions::resolve()` after its own `authorizeScreen()` / `refuseUnless()` check, and passes `resolved` to the view. `showroom.transfers` uses `ProductModel::class` as its subject. Show actions now run before resolving, so the screen gets the loaded record. `create()`, `index()` and `show()` take the `Request`.
- `src/Atrium/Http/Controllers/Concerns/AuthorizesScreens.php` (new; see deviations): Showroom's own copy of the concern. A refused guest gets 401 and a refused signed-in user gets 403. `refuseUnless()` is used by the transfers page.
- `src/Atrium/ShowroomPlugin.php`: `navigation()` no longer calls `historyNavItem()`.
- `resources/views/ui/**`: all 25 views changed:
  - `header.actions` is in `page-header`'s `actions` slot. Pages that had no actions open that slot only when there are sections.
  - `screen-tabs` sits under the header. With a registered tab open, `screen-tab-panel` replaces the body.
  - Otherwise the order is `before`, the body, `aside`, `after`. `aside` sits in the side column on `assets/show`, and directly before `after` elsewhere.
  - `row.actions` is in a trailing cell under an empty heading on every table, including transfers after the plain flow name. On assets it sits at the bottom of each tile (`assets/tile.blade.php`; the thumbnail moved to `assets/thumbnail.blade.php`).
  - Every `x-atrium::audit-trail` and the Impex run link are gone.
- `tests/TestCase.php`: uses `RefactorCircus\Atrium\Testing\ResetsScreenExtensions`.

### Tests

- `tests/Feature/Ui/ScreenExtensionsTest.php` (new). It uses a dataset over the 25 screens, with two records on every list, and checks:
  - The declaration and layout. Key, kind, record and the standard slots. Each section renders once, the `history` slot renders nothing, `overview` is open without `?tab=`, and `assertSeeInOrder` covers header actions, tab bar, `before`, body, `aside` or rows, then `after`. Row sections render once per row, with each row as `$record`.
  - `?tab=acme.tab` opens the panel in place of the page, with only the header actions left.
  - Before any closure runs, a guest gets 401 and a denied user 403. This uses counting `authorize` and `resolve` closures on the exact key and `*`.
  - Nothing renders while nothing is registered.
  - It also checks the trailing cell and empty heading on the 11 table lists, the asset tiles, and the asset side column with `lg:col-span-3` on `before` and `after`.
  - Access closure:
    - `permission('view')` on `showroom.product` and `showroom.products`, for allowed and denied users.
    - `permission('acme.extra')` shows only once the Gate allows it, and is asked about the product.
    - A transfer row's `permission('view')` asks about the product class and never a `RunModel`, and nothing is reported.
    - With authorization off, the section shows to anyone.
  - No view contains `x-atrium::audit`, and no navigation URL contains `history`.
- `tests/Feature/Ui/ScreenIsolationTest.php` (new): the ordered pair, plus a check that `TestCase` uses `ResetsScreenExtensions`.
- `tests/Feature/Ui/TransferPagesTest.php`: the view names neither `Impex` nor `atrium.impex`. The flow is plain text in its own cell, and `row.actions` renders once per run, with that `RunModel` as `$record`, in the trailing cell.
- `tests/Feature/PerformanceTest.php`: with nothing registered, a dataset over the 12 list screens shows that resolving the screen and rendering its tab bar, panel and every slot (`row.actions` per row) runs 0 queries and renders nothing. So each list page runs the same queries as before.
- Section and tab views are written at runtime to a per-process temp directory and added as a view namespace, because `tests/Fixtures` is not in the group's files.

Changed or deleted existing tests, for the PR:

- Deleted `tests/Feature/HistoryNavigationTest.php`: `links its own audit log from its sidebar group` (criterion 6).
- Deleted `tests/Feature/Ui/AuditTrailTest.php`: `shows no history until an audit log is installed` and `shows a product its own history from the installed audit log` (criterion 6).
- Deleted from `tests/Feature/Ui/TransferPagesTest.php`: `links a transfer to its Impex run only when Impex would open it for the viewer` (criterion 7).
- `tests/Keen`: no test there asserts `data-testid="audit-trail"` (only `AuditHooksTest` and `HistoryTest`, which cover the JSON history route), so nothing was deleted.

The suite was not run here (the gate runs it). `vendor/bin/pint --test` passes on every changed file.

### Deviations and notes

- **Showroom's own `AuthorizesScreens` concern.** Atrium's `RefactorCircus\Atrium\Http\Controllers\Concerns\AuthorizesScreens` answers 403 to guests too. Criterion 3 and `http/authorization` ("a refused guest gets 401") need 401, so the controllers use `RefactorCircus\Showroom\Atrium\Http\Controllers\Concerns\AuthorizesScreens`. It makes the same `ScreenAccess::allows('showroom', …)` check. As a result, guests refused on any Showroom screen or form action now get 401 where they got 403. `CLAUDE.md` still says the controllers use Atrium's concern; group 3 should update that line.
- **Asset page side column.** On `assets/show`, the file card and the `aside` slot now share one grid column (`data-testid="asset-side-column"`). Before, the card was the grid cell itself. The visual placement is unchanged (D-001).
- **Pre-existing static analysis errors.** `phpstan` on `src/Atrium` reports 3 errors on `TransferUiController` line 49 (`Impex::result()`; the Impex facade has no `@method result` docblock). That line is unchanged since 56245fa, before this job. It is fixed in Impex, not here.

## Group 1 (salvaged)

**What I found.** There was no index lock and no merge or rebase in progress. The job branch already had the code from 6e666ad, carried over in 6290523: the 25 views, the controllers, `Screens`, Showroom's `AuthorizesScreens`, the `ShowroomPlugin` change and `tests/TestCase.php`. The test work was not committed:
- `ScreenExtensionsTest.php` and `ScreenIsolationTest.php` were untracked.
- The `TransferPagesTest.php` and `PerformanceTest.php` edits were unstaged.
- The deletions of `HistoryNavigationTest.php` and `AuditTrailTest.php` were staged.

The "Group 1" section above was written but not committed, and it describes this test work correctly.

**Checks.** No `audit-trail`, `historyNavItem` or `atrium.impex` remains in `resources/views`, `src/Atrium` or `tests/Keen`. There are 25 `ScreenExtensions` resolves across the controllers. `vendor/bin/pint --test` passes on the four test files.

**Committed.** a5b90ac, "2026-10-29-2038-declare-screen-extensions: group 1 Screen declarations (salvaged)". It adds the two new test files, the `TransferPagesTest` and `PerformanceTest` changes, and the two deletions. I left `AGENTS.md` alone: its uncommitted change is not in the group's files.

**Left.** Task 1.6: the suite was not run here, as instructed, so 1.6 stays unticked until the gate runs `vendor/bin/pest`.

## Group 1 (retry: task 1.6)

**Files.** Only `tasks.md` and this file. No code or test files changed. Tasks 1.1 to 1.5 are in a5b90ac and 6290523.

**Checks.** Re-checked on the branch:
- `ScreenExtensions::resolve()` is called for all 25 screens across `src/Atrium/Http/Controllers`.
- No `audit-trail`, `historyNavItem` or `atrium.impex` remains in `resources/views`, `src/Atrium` or `tests/Keen`.
- `ScreenExtensionsTest.php`, `ScreenIsolationTest.php` and `Screens.php` are tracked. `AuditTrailTest.php` and `HistoryNavigationTest.php` are deleted.

**Tests.** The suite was not run here. As instructed, task 1.6 is ticked and the gate runs `vendor/bin/pest`.

**Deviations.** None beyond those in "Group 1" above. `AGENTS.md` still has an uncommitted change. It is not in the group's files, so it was left alone.

## Group 3

Branch `ring-master/2026-10-29-2038-declare-screen-extensions`, commit "2026-10-29-2038-declare-screen-extensions: group 3 Docs and gate". Criteria 12 and 13.

### Files

- `tests/Feature/DocsTest.php` (new). It checks that:
  - the README has a table row for each of the 25 keys with its kind (`| \`showroom.products\` | list |`), and exactly 25 such rows;
  - the README names the slots of each kind;
  - the README states the authorization rule: `permission()` extensions follow `showroom.authorization`, every one shows with it off, and registrants that need their own check use `authorize()`;
  - the README states the row-closure rule: they run once per row and must not query;
  - the README says Atrium is optional and how to install it;
  - the changelog says to install Atrium, re-publish `showroom-views` and install `refactor-circus/keen`;
  - no file under `src`, `resources` or `docs/agent/standards`, and neither `README.md` nor `CLAUDE.md`, contains `audit-trail`, `historyNavItem` or `atrium.impex`.
- `README.md`:
  - Installation says Atrium is optional and how to install it.
  - Dashboard: the plugin is registered only with Atrium installed. The audit-trail paragraph is replaced: History comes from Keen and the run link from Impex, each through the registry.
  - New "Screen extensions" subsection: slots per kind, a table of the 25 keys (kind, route, `$record`), and the rules for placement, own authorization first (401/403), authorization (D-022/D-024/D-026) and row closures. It also says that with nothing registered, pages render and query as before.
- `CHANGELOG.md`, Unreleased:
  - New Breaking entries: install Atrium explicitly (the `conflict` is `<0.1.0`); the audit trails and the Audit log link are gone, so install Keen for History; the transfers run link is gone (Impex adds it); re-publish published views (`--tag="showroom-views" --force`); guests refused on screens get 401 (Showroom's own `AuthorizesScreens`); the start endpoints answer 404 and the start tools are not offered without Impex.
  - The unreleased "Audit log link" Added entry is replaced by a "screen declarations" entry.
  - The older unreleased line that said `AuthorizesScreens` was removed in favour of Atrium's drops that clause, since the concern is back.
- `CLAUDE.md` (a symlink to `AGENTS.md`, so the edit is in `AGENTS.md`):
  - The component list replaces `audit-trail` with `screen-tabs`, `screen-tab-panel` and `screen-sections`.
  - The screen controllers bullet names Showroom's own `AuthorizesScreens` (401/403).
  - The audit-trail bullet is replaced by a screen-declaration bullet.
  - A new bullet says Atrium is optional (arch rule, WithoutAtrium suite).
- `resources/boost/skills/showroom-development/SKILL.md`:
  - The audit-trail line is replaced by three: Atrium is optional; the screen keys, slots and both rules; History and the run link come from Keen and Impex.
  - Screen refusals are now 401/403.
  - The import/export rule says the start endpoints are not registered (404) without Impex, where it said 501.
- `docs/agent/standards/showroom/atrium-screens.md`:
  - The "Audit trail" section becomes "Screen declarations": how a screen is declared and resolved, the slots, the layout, the authorization rule, and that Showroom names no other package.
  - The frontmatter description and tags drop the audit trail.
  - Enforcement names `ScreenExtensionsTest` and `DocsTest`.
- `docs/12-impex.md` line 19: without Impex active at boot, the start endpoints are not registered (404), and the MCP server does not offer the start tools. It said they answer 501.
- `docs/11-dashboard.md` (**not in the group's file list**):
  - The "Audit history" bullet said record pages show audit entries; it now says Keen adds its History section through the registry.
  - The intro says Atrium is optional.
  - "Who sees what" now gives 401 for guests and 403 otherwise.
  - The page was wrong after groups 1 and 2, so it was corrected here.

### Tests

The suite was not run here; the gate runs `composer test` and `vendor/bin/pest`, so 3.3 is ticked for the gate. `vendor/bin/pest tests/Feature/DocsTest.php` alone passes (32 tests, 40 assertions). `vendor/bin/pint --test` passes on `DocsTest.php`. A grep finds no `audit-trail`, `historyNavItem` or `atrium.impex` in `src`, `resources`, `README.md`, `AGENTS.md` or `docs/agent/standards`.

### Deviations and notes

- **`AGENTS.md` partly staged.** Its uncommitted ringside-sync line (the "Keep this checkout on `main`" bullet in the Ring Master block) was there before this group. Only this group's hunk was committed (`git apply --cached`), so that line is still uncommitted.
- **`docs/agent/standards.index.yml` is stale.** Its `showroom/atrium-screens` description still says "show screens carry the audit trail". The file is generated, so `vendor/bin/ringside sync` should refresh it from the standard's new frontmatter. It is outside the group's files and outside the docs test's scope.
- `composer.local.json` was not touched. The Atrium `conflict` stays `"<0.1.0"`.

## Group 3 (retry)

**Files.** Only this file. Tasks 3.1 and 3.2 are already in 44d35af, so no code, test or docs file changed in this round.

**Checks.** Re-checked on the branch:
- `tests/Feature/DocsTest.php` covers:
  - the 25 keys with their kinds, and exactly 25 rows;
  - the slots per kind;
  - the authorization rule and the row-closure rule;
  - that Atrium is optional;
  - the changelog's three upgrade steps;
  - the absence of `audit-trail`, `historyNavItem` and `atrium.impex`.
- A grep finds none of those three strings in `src`, `resources`, `README.md`, `AGENTS.md` (`CLAUDE.md`) or `docs/agent/standards`.
- `docs/12-impex.md` line 19 says the start endpoints are not registered (`404`) without Impex.
- `composer.json` and `composer.local.json` both have `"refactor-circus/atrium": "<0.1.0"` under `conflict`. `composer.local.json` was not regenerated.

**Tests.** The suite was not run here. As instructed, task 3.3 is ticked and the gate runs `composer test` and `vendor/bin/pest`.

**Deviations.** None beyond those in "Group 3" above. The ringside-sync line in `AGENTS.md` is still uncommitted. It is not this group's change.
