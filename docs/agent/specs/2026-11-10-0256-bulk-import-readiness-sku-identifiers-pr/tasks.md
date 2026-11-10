# Tasks: Bulk import readiness: SKU identifiers, slugs, external IDs, flags, batch upsert

Order:

- Groups 1 and 2 run in parallel.
- Group 6 (flag definitions) runs in parallel with groups 3–5.
- After that the work is sequential, because the groups share the product and owner actions, Data classes, resources and search engines.

## Group 1: Config and SKU identifier rule
depends on: none
files: config/showroom.php, src/Domains/Product/Data/CreateProductData.php, tests/Feature/Api/ProductIdentifierTest.php
criteria: 1, 2

- [x] 1.1 Write tests for:
  - identifiers with spaces and `/` (the AC 1 examples)
  - the boundary dataset: empty, leading/trailing whitespace, control characters, 191 characters
  - a stricter host pattern failing at `identifier`
  - a non-compiling pattern throwing
- [x] 1.2 Add every new config key in one pass, each with a banner section (`tables.*` and `policies.flag` move to groups 5 and 6; see build.md, Group 1):
  - `products.identifier_pattern`
  - `lookup.products` and `lookup.owners` (`id` or `slug`, default `id`)
  - `tables.external_ids`, `tables.flags` and `tables.flag_assignments`
  - `policies.flag`
- [x] 1.3 In `CreateProductData::rules()`, read the pattern as an array rule. Check that it compiles and fail loud if it doesn't.
- [x] 1.4 Run vendor/bin/pest; all green

## Group 2: Owner type may parent itself
depends on: none
files: src/Domains/Owner/Data/CreateOwnerTypeData.php, src/Domains/Owner/Actions/CreateOwnerTypeAction.php, src/Domains/Owner/Concerns/WritesOwnerTypes.php, tests/Feature/Api/OwnerTypeSelfParentTest.php
criteria: 24

- [x] 2.1 Write tests:
  - create `vendor` with `parent_types: ["vendor"]`
  - create a vendor owner under a vendor owner
  - an unknown parent type is still refused
- [x] 2.2 Let `parent_types.*` equal the incoming `code`, and resolve the parent types after the type row exists.
- [x] 2.3 Run vendor/bin/pest; all green

## Group 3: Slugs for products and owners
depends on: 1
files: database/migrations/2026_11_10_000001_add_slugs_to_showroom_products_and_owners.php, src/Support/Slugs.php, src/Domains/Product/Models/ProductModel.php, src/Domains/Owner/Models/OwnerModel.php, src/Domains/Product/Actions/CreateProductAction.php, src/Domains/Owner/Actions/CreateOwnerAction.php, src/Domains/Product/Data/CreateProductData.php, src/Domains/Product/Data/UpdateProductData.php, src/Domains/Owner/Data/CreateOwnerData.php, src/Domains/Owner/Data/UpdateOwnerData.php, src/Domains/Product/Resources/ProductResource.php, src/Domains/Owner/Resources/OwnerResource.php, src/Domains/Search/Services/ProductDocument.php, src/Impex/ExportRecord.php, src/Impex/ProductRows.php, tests/Unit/SlugsTest.php, tests/Feature/SlugsTest.php, tests/Feature/SlugBackfillTest.php
criteria: 3, 4, 5, 6, 7, 12

- [x] 3.1 Write tests for:
  - the `Slugs` generator: collisions, no gaps, 191-character truncation, in-call collisions, the empty-source fallback (`product`, `owner`), reserved words
  - product and owner creation assigning slugs
  - a `slug` sent on create or update being refused (round-trip a full resource body)
  - slugs unchanged after values, labels, parent or code changes
  - the backfill: chunking, idempotent re-run, empty-slug rows
  - `slug` in resources, the search document (`slug`, `owner_slugs`), exports and MCP outputs
- [x] 3.2 Implement `Support\Slugs` (`for`, `forMany`, `RESERVED`, retry once on a unique race), and the migration (nullable column, chunked `slug IS NULL` backfill, then non-null and unique).
- [x] 3.3 Wire slugs into the models, the create actions and the Data `prohibited` rules. Add `slug` to resources, `ProductDocument` and exports.
- [x] 3.4 Run vendor/bin/pest; all green

## Group 4: Record lookup by id or slug
depends on: 3
files: src/Support/RecordLookup.php, src/Support/LookupKey.php, src/ShowroomServiceProvider.php, src/Domains/Product/routes.php, src/Domains/Workflow/routes.php, src/Domains/Owner/routes.php, src/Atrium/ShowroomPlugin.php, src/Domains/Product/Http/Requests/ShowProductRequest.php, src/Domains/Product/Http/Requests/UpdateProductRequest.php, src/Domains/Product/Http/Requests/DeleteProductRequest.php, src/Domains/Workflow/Http/Requests/TransitionProductRequest.php, src/Domains/Workflow/Http/Requests/IndexProductVersionsRequest.php, src/Domains/Workflow/Http/Requests/ShowProductVersionRequest.php, src/Domains/Workflow/Http/Requests/RevertProductRequest.php, src/Domains/Owner/Http/Requests/ShowOwnerRequest.php, src/Domains/Owner/Http/Requests/UpdateOwnerRequest.php, src/Domains/Owner/Http/Requests/DeleteOwnerRequest.php, src/Domains/Product/Mcp/Tools/*, src/Domains/Owner/Mcp/Tools/ShowOwnerTool.php, src/Domains/Owner/Mcp/Tools/UpdateOwnerTool.php, src/Domains/Owner/Mcp/Tools/DeleteOwnerTool.php, src/Domains/Workflow/Mcp/Tools/*, resources/views/ui/products/*, resources/views/ui/owners/*, tests/Feature/RecordLookupTest.php, docs/agent/standards-feedback.md
criteria: 8, 9, 10, 11

- [x] 4.1 Write tests:
  - a dataset over the `route:list` product and owner routes (API, Workflow, Atrium): `id` binds by default, while slug, identifier and code answer `404`
  - per configured key, the configured key binds
  - `?by=` and MCP `by` override the config
  - an invalid `by` gives `422` or an MCP validation error
  - an invalid config value fails at boot
  - flag routes keep `code` (once group 6 lands; skip-marked until then)
- [x] 4.2 Implement:
  - the `LookupKey` enum (`Id`, `Slug`) and the `RecordLookup` resolver
  - explicit `{product}` and `{owner}` binders that read `?by=`
  - the boot check in the provider
  - `by` on the listed requests and MCP tools
  - Atrium links following the configured key
- [x] 4.3 Add the D-001 deviation row to `docs/agent/standards-feedback.md`.
- [x] 4.4 Run vendor/bin/pest; all green

## Group 5: External IDs
depends on: 4
files: src/Domains/ExternalId/** (no domain provider), src/ShowroomServiceProvider.php, database/migrations/2026_11_10_000002_create_showroom_external_ids_table.php, src/Support/KeyNormalizer.php, src/Domains/Product/Models/ProductModel.php, src/Domains/Owner/Models/OwnerModel.php, src/Domains/Product/Actions/CreateProductAction.php, src/Domains/Product/Actions/UpdateProductAction.php, src/Domains/Product/Actions/DeleteProductAction.php, src/Domains/Owner/Actions/CreateOwnerAction.php, src/Domains/Owner/Actions/UpdateOwnerAction.php, src/Domains/Owner/Actions/DeleteOwnerAction.php, src/Domains/Product/Data/*.php, src/Domains/Owner/Data/*.php, src/Domains/Product/Resources/ProductResource.php, src/Domains/Owner/Resources/OwnerResource.php, src/Domains/Search/Services/ProductDocument.php, src/Domains/Search/Services/DatabaseEngine.php, src/Domains/Search/Services/ScoutEngine.php, src/Domains/Owner/Actions/ListOwnersAction.php, tests/Feature/ExternalIdsTest.php, tests/Unit/KeyNormalizerTest.php
criteria: 13, 14, 15, 16, 36

- [x] 5.1 Write tests:
  - writing the map on create and update: partial update, `null` removes, system and id rules
  - uniqueness per subject type and system, an atomic failure, an in-batch duplicate
  - the same id allowed on one product and one owner
  - `{}` for an empty map
  - the `external[...]` filter on both engines, with escaped values (`"`, `\`, ` OR `)
  - deleting a record deletes its external ids
  - `KeyNormalizer` case and accent folding, and collisions that differ only by case
- [x] 5.2 Implement:
  - the `ExternalId` domain (model, migration, scoped `ExternalIds` service, morph aliases); register the binding and morph aliases in `ShowroomServiceProvider`, not `DomainServiceProvider` (group 6 owns that file)
  - `KeyNormalizer`
  - writing external ids from the create and update actions; deleting them on delete
  - resources, the list filters and both search engines
- [x] 5.3 Run vendor/bin/pest; all green

## Group 6: Flag definitions
depends on: 1
files: src/Domains/Flag/** (except Services/Flags.php), src/Domains/DomainServiceProvider.php, src/Mcp/ShowroomServer.php, database/migrations/2026_11_10_000003_create_showroom_flag_tables.php, tests/Feature/Api/FlagApiTest.php, tests/Feature/FlagActionsTest.php
criteria: 17, 18

- [x] 6.1 Write tests:
  - flag create, list, show, update and delete over actions, HTTP and MCP
  - `FlagPolicy` abilities, including `assign`
  - `code` cannot be changed
  - `applies_to` must be a non-empty subset of `product` and `owner`
  - deleting an assigned flag is refused
  - narrowing `applies_to` while the flag is assigned is refused (seed `flag_assignments` rows directly)
- [x] 6.2 Implement the Flag domain:
  - model, migration (both tables), policy
  - actions with Data and action-event pairs
  - requests, controller, resource, routes, MCP tools
  - registration in the domain provider and the MCP server
- [x] 6.3 Run vendor/bin/pest; all green

## Group 7: Flag assignment and effective flags
depends on: 5, 6
files: src/Domains/Flag/Services/Flags.php, src/Domains/Flag/Concerns/HasFlags.php, src/Domains/Product/Models/ProductModel.php, src/Domains/Owner/Models/OwnerModel.php, src/Domains/Product/Actions/CreateProductAction.php, src/Domains/Product/Actions/UpdateProductAction.php, src/Domains/Product/Actions/DeleteProductAction.php, src/Domains/Product/Actions/ShowProductAction.php, src/Domains/Owner/Actions/CreateOwnerAction.php, src/Domains/Owner/Actions/UpdateOwnerAction.php, src/Domains/Owner/Actions/DeleteOwnerAction.php, src/Domains/Owner/Actions/ShowOwnerAction.php, src/Domains/Flag/Actions/DeleteFlagAction.php, src/Domains/Product/Data/*.php, src/Domains/Owner/Data/*.php, src/Domains/Product/Resources/ProductResource.php, src/Domains/Owner/Resources/OwnerResource.php, tests/Feature/FlagAssignmentTest.php
criteria: 19, 20, 21, 33, 37

Resumes from checkpoint commit 4d664b3, which already has a partial `Flags` service, a `HasFlags` concern and edits to the actions, Data and resources. Read the diff first (`git show 4d664b3 --stat`) and finish that work rather than starting over. Search engine and `ProductDocument` changes in that commit belong to group 12: leave them alone.

- [x] 7.1 Write tests in tests/Feature/FlagAssignmentTest.php:
  - `flags` on product and owner create and update: replaces the set; unknown codes and wrong `applies_to` refused at `flags.{n}`
  - `assign` required only when flags change (actions, HTTP and MCP; owners too)
  - effective flags through a grandchild owner and a root-model-owner variant; a child cannot drop an inherited flag
  - `flags` and `effective_flags` in resources and MCP output
  - assignments deleted with their subject; orphan assignments ignored by flag delete
- [x] 7.2 Finish the `Flags` service (`write`, `own`, `effective`), the `assign` check and flag writes in the create and update actions, cleanup on delete, and the resource fields.
- [x] 7.3 Run vendor/bin/pest tests/Feature/FlagAssignmentTest.php tests/Feature/FlagActionsTest.php tests/Feature/Api/FlagApiTest.php; all green

## Group 8: Batch upsert action
depends on: 7
files: src/Domains/Product/Actions/UpsertProductsAction.php, src/Domains/Product/Data/UpsertProductsData.php, src/Domains/Product/Data/UpsertProductsResult.php, src/Domains/Product/Enums/UpsertMode.php, src/Domains/Product/Events/ProductsUpsertingActionEvent.php, src/Domains/Product/Events/ProductsUpsertedActionEvent.php, src/Domains/Product/Services/ProductChangeCapture.php, src/Domains/Product/Services/BulkProductWriter.php, src/Domains/Product/ProductServiceProvider.php, tests/Feature/UpsertProductsActionTest.php, tests/Feature/UpsertProductsParityTest.php, tests/Feature/UpsertProductsPerformanceTest.php, tests/Unit/ProductChangeCaptureTest.php
criteria: 25, 26, 27, 28, 29, 35, 36, 37, 38, 39

- [x] 8.1 Write tests:
  - modes and matching by identifier or external system, including a match that differs only by case
  - per-record validation, with failures reported by index, identifier and errors
  - the unchanged preview: no rows, versions, syncs or events; the query count is flat between 10 and 100 records
  - created and updated version counts
  - one index sync per batch, with a sub-linear query bound for new records (the implementer picks the constant; it must fail on per-record growth)
  - `assign` refusal as a per-record failure
  - parity between the bulk path and `CreateProductAction`
  - a mixed chunk, and a forced bulk failure that falls back to per-record writes
  - `ProductChangeCapture::suppress()` restoring its state in a `finally`
- [x] 8.2 Implement:
  - `UpsertProductsData`, the result Data, the `UpsertMode` enum and the event pair
  - `ProductChangeCapture` (scoped)
  - `BulkProductWriter`, sharing the write step with the create action
  - the action flow: validate (including `assign`) → load → preview → bulk or per-record write inside `ProductIndex::deferring()`
- [x] 8.3 Run vendor/bin/pest; all green

## Group 9: Impex integration
depends on: 8, 13, 14
files: src/Impex/Actions/UpsertProductChunk.php, src/Impex/Actions/ImportProductRow.php (removed), src/Impex/Sources/InlineProductSource.php, src/Impex/Sources/FileProductSource.php, src/Impex/Flows/UpsertProductsFlow.php, src/Impex/Flows/ImportProductsFlow.php, src/Impex/Webhooks/CaptureProductChanges.php, src/Impex/ImpexIntegration.php, tests/ArchTest.php, tests/Feature/Impex/ImportExportTest.php, tests/Feature/Impex/ProductWebhooksTest.php, tests/Feature/Impex/UpsertChunkTest.php
criteria: 30, 31, 32

Group 13 already moved `tests/Feature/Impex/ImportExportTest.php` and `ProductWebhooksTest.php` to `id` lookup. Keep that when changing them.

- [x] 9.1 Write tests:
  - both flows process per chunk through the action and accept `capture_changes`
  - run results have `total`, `succeeded`, `failed` and `unchanged`; per-row failures are kept with the global row index
  - `CaptureProductChanges` records nothing while suppressed and records by default
  - an arch rule that core (outside `src/Impex`) never references Impex
- [x] 9.2 Implement:
  - `UpsertProductChunk`, replacing `ImportProductRow` and its per-row event
  - chunked sources and the flow arguments
  - the suppression check in `CaptureProductChanges`
- [ ] 9.3 Run vendor/bin/pest; all green

## Group 10: Atrium screens
depends on: 4, 7
files: src/Atrium/ShowroomPlugin.php, src/Atrium/FlagScreen.php, src/Atrium/Http/Controllers/FlagUiController.php, src/Atrium/Http/Controllers/ProductUiController.php, src/Atrium/Http/Controllers/OwnerUiController.php, resources/views/ui/flags/*, resources/views/ui/products/*, resources/views/ui/owners/*, tests/Feature/Ui/FlagPagesTest.php, tests/Feature/Ui/ProductPagesTest.php, tests/Feature/Ui/OwnerPagesTest.php
criteria: 40, 41

- [x] 10.1 Write tests:
  - the flag-definitions screen (list, create, edit, delete), with the refusal message shown when a delete is refused
  - product and owner screens: slug read-only, the external_ids map editor, own flags limited to flags that apply to the subject and hidden without `assign`, effective flags read-only with inherited ones marked
  - links use the configured lookup key
- [x] 10.2 Implement the screens and fields through the existing Atrium screen registry, using only Atrium components.
- [x] 10.3 Run vendor/bin/pest; all green

## Group 11: Docs and changelog
depends on: 9, 10, 12, 13, 14, 15
files: CHANGELOG.md, docs/03-families.md, docs/04-products.md, docs/05-search.md, docs/06-ownership.md, docs/09-api.md, docs/10-mcp.md, docs/12-impex.md, docs/13-configuration.md, docs/14-flags.md, tests/Feature/DocsTest.php
criteria: none (upgrade notes from the spec's Design)

- [ ] 11.1 Extend `DocsTest` for the new flags doc page and the documented config keys.
- [ ] 11.2 Update the docs:
  - SKU identifiers, slugs, record lookup and `by`, external ids
  - flags: a new page, inheritance, `assign`
  - batch upsert and the webhook opt-out
- [ ] 11.3 Add changelog upgrade notes:
  - the breaking change to lookup keys
  - read-only keys on update
  - `ImportProductRow` and `ProductRowImportedActionEvent` replaced
  - unique-index lock time on large installs
  - Scout settings
- [ ] 11.4 Run vendor/bin/pest; all green

## Group 12: Flag search and owner reindex
depends on: 7
files: src/Domains/Search/Services/ProductDocument.php, src/Domains/Search/Services/DatabaseEngine.php, src/Domains/Search/Services/ScoutEngine.php, src/Domains/Search/Data/ProductQuery.php, src/Domains/Product/Data/ListProductsData.php, src/Domains/Product/Actions/ListProductsAction.php, src/Domains/Owner/Data/ListOwnersData.php, src/Domains/Owner/Actions/ListOwnersAction.php, src/Domains/Owner/Actions/UpdateOwnerAction.php, tests/Feature/Search/FlagSearchTest.php, tests/Feature/PerformanceTest.php
criteria: 22, 23, 34

Checkpoint commit 4d664b3 already has partial edits to `DatabaseEngine`, `ScoutEngine`, `ProductDocument` and `ProductQuery`. Finish them rather than starting over.

- [x] 12.1 Write tests in tests/Feature/Search/FlagSearchTest.php:
  - one shared dataset on both engines (own, inherited, root-model-owner variants, several flags ANDed); flag codes escaped in Scout filters
  - the owner listing filters on effective flags
  - changing an owner's flags, or moving an owner, queues `QueueProductSyncs` (queue fake), and the search reflects it after the sync
  - Scout settings list `slug`, `external_ids`, `flags` and `owner_slugs`
- [x] 12.2 Implement `effective_flags` in `ProductDocument`, the `flags[]` filter on both engines and the owner listing, the reindex in `UpdateOwnerAction` when flags change, and the Scout settings.
- [x] 12.3 Run vendor/bin/pest tests/Feature/Search; all green

## Group 13: Existing API and search tests on id lookup
depends on: 8, 10, 12
files: tests/Feature/Api/WorkflowApiTest.php, tests/Feature/Api/ProductApiTest.php, tests/Feature/Api/AssetApiTest.php, tests/Feature/Api/CompletenessApiTest.php, tests/Feature/Api/ProductPollingTest.php, tests/Feature/Api/CategoryApiTest.php, tests/Feature/Api/AssociationApiTest.php, tests/Feature/Api/OwnershipApiTest.php, tests/Feature/Api/ChannelApiTest.php, tests/Feature/PolicyTest.php, tests/Feature/PerformanceTest.php, tests/Feature/Search/ScoutEngineTest.php, tests/Feature/RecordLookupTest.php, tests/Keen/AuditHooksTest.php, tests/Modes/RenamedTables/RenamedTablesTest.php, tests/Feature/Impex/ImportExportTest.php, tests/Feature/Impex/ProductWebhooksTest.php
criteria: 8

Group 4 made products and owners bind by ULID `id` by default (AC 8, D-001/D-010), so older tests that build URLs or MCP arguments from an identifier or code now get `404`. Update the tests, not the binding. Include the two Impex tests, so the suite is green before group 9. Group 9 rewrites them afterwards. Leave the UI and MCP tests to group 14.

- [x] 13.1 Run the files listed above and note every failure that is a `404` or "Not found" on a product or owner URL or MCP argument.
- [x] 13.2 Change those tests to address records by `id`, using the model's key or the `id` from the resource response. Don't add slug or identifier fallbacks to the code.
- [x] 13.3 In `RecordLookupTest`, limit the "lists every route that takes a product or an owner" dataset to Showroom's own routes (route names starting `showroom.`, plus the Atrium Showroom screens). Other packages, such as Impex's `runs/{run}/owners/{owner}`, define their own `{owner}` parameters.
- [x] 13.4 Run vendor/bin/pest on the listed files; all green

## Group 14: Existing UI and MCP tests on id lookup
depends on: 8, 10, 12
files: tests/Feature/Ui/WorkflowPagesTest.php, tests/Feature/Ui/AssociationPagesTest.php, tests/Feature/Ui/WidgetsTest.php, tests/Feature/Ui/ScreenAuthorizationTest.php, tests/Feature/Ui/ScreenExtensionsTest.php, tests/Feature/Ui/ScreenIsolationTest.php, tests/Feature/Ui/AssetPagesTest.php, tests/Feature/Ui/CategoryPagesTest.php, tests/Feature/Ui/ChannelPagesTest.php, tests/Feature/McpTest.php
criteria: 8

The same id-lookup fallout as group 13, for the Atrium screens and MCP tools.

- [x] 14.1 Run the files listed above and note every failure that is a `404` or "Not found" on a product or owner screen URL or MCP argument.
- [x] 14.2 Change those tests to address records by `id`. If a failure is not about lookup, such as a screen extension change from group 10, fix the test or the screen so it matches the spec, and say which in build.md.
- [x] 14.3 Run vendor/bin/pest on the listed files; all green


## Group 15: Apply build decisions D-011 and D-012
depends on: 9
files: src/Impex/Flows/UpsertProductsFlow.php, src/Impex/Flows/ImportProductsFlow.php, tests/Feature/Impex/UpsertChunkTest.php, tests/ArchTest.php
criteria: 31, 32

See the spec addendum for D-011 and D-012.

- [x] 15.1 Write tests:
  - each flow run with `--capture-changes=0` records nothing for product webhooks, and records by default
  - the arch rule passes with exactly the two named exceptions, `Domains\Transfer` and `Atrium\Http\Controllers\TransferUiController`
- [x] 15.2 Declare `?bool $captureChanges = null` (null means true) on both flows, and set the arch rule's `ignoring()` to exactly those two exceptions.
- [x] 15.3 Run vendor/bin/pest; all green
