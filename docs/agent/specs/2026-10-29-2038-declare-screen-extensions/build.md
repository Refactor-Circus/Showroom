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
