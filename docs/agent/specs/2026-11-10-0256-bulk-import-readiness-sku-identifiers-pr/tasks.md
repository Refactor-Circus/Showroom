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

## Group 7: Flag assignment, effective flags and search
depends on: 5, 6
files: src/Domains/Flag/Services/Flags.php, src/Domains/Product/Models/ProductModel.php, src/Domains/Owner/Models/OwnerModel.php, src/Domains/Product/Actions/CreateProductAction.php, src/Domains/Product/Actions/UpdateProductAction.php, src/Domains/Product/Actions/DeleteProductAction.php, src/Domains/Owner/Actions/CreateOwnerAction.php, src/Domains/Owner/Actions/UpdateOwnerAction.php, src/Domains/Owner/Actions/DeleteOwnerAction.php, src/Domains/Owner/Actions/ListOwnersAction.php, src/Domains/Flag/Actions/DeleteFlagAction.php, src/Domains/Product/Data/*.php, src/Domains/Owner/Data/*.php, src/Domains/Product/Resources/ProductResource.php, src/Domains/Owner/Resources/OwnerResource.php, src/Domains/Search/Services/ProductDocument.php, src/Domains/Search/Services/DatabaseEngine.php, src/Domains/Search/Services/ScoutEngine.php, tests/Feature/FlagAssignmentTest.php, tests/Feature/Search/FlagSearchTest.php
criteria: 19, 20, 21, 22, 23, 33, 34, 37

- [ ] 7.1 Write tests:
  - `flags` on product and owner create and update: replaces the set; unknown codes and wrong `applies_to` refused at `flags.{n}`
  - `assign` required only when flags change (HTTP, MCP and actions; owners too)
  - effective flags through a grandchild owner and a root-model-owner variant; a child cannot drop an inherited flag
  - `flags` and `effective_flags` in resources and MCP output
  - one shared search dataset on both engines (own, inherited, variants, several flags ANDed), plus the owner listing filter
  - changing an owner's flags or moving an owner queues `QueueProductSyncs` (queue fake), and the search reflects it after the sync
  - assignments deleted with their subject; orphan assignments ignored by flag delete
  - Scout settings list `slug`, `external_ids`, `flags` and `owner_slugs`
- [ ] 7.2 Implement:
  - the `Flags` service (`write`, `own`, `effective`)
  - the `assign` check and flag writes in the create and update actions
  - cleanup of assignments on delete
  - the reindex in `UpdateOwnerAction`
  - resources, `ProductDocument`, both engines and the Scout settings
- [ ] 7.3 Run vendor/bin/pest; all green

## Group 8: Batch upsert action
depends on: 7
files: src/Domains/Product/Actions/UpsertProductsAction.php, src/Domains/Product/Data/UpsertProductsData.php, src/Domains/Product/Data/UpsertProductsResult.php, src/Domains/Product/Enums/UpsertMode.php, src/Domains/Product/Events/ProductsUpsertingActionEvent.php, src/Domains/Product/Events/ProductsUpsertedActionEvent.php, src/Domains/Product/Services/ProductChangeCapture.php, src/Domains/Product/Services/BulkProductWriter.php, src/Domains/Product/ProductServiceProvider.php, tests/Feature/UpsertProductsActionTest.php, tests/Feature/UpsertProductsParityTest.php, tests/Feature/UpsertProductsPerformanceTest.php, tests/Unit/ProductChangeCaptureTest.php
criteria: 25, 26, 27, 28, 29, 35, 36, 37, 38, 39

- [ ] 8.1 Write tests:
  - modes and matching by identifier or external system, including a match that differs only by case
  - per-record validation, with failures reported by index, identifier and errors
  - the unchanged preview: no rows, versions, syncs or events; the query count is flat between 10 and 100 records
  - created and updated version counts
  - one index sync per batch, with a sub-linear query bound for new records (the implementer picks the constant; it must fail on per-record growth)
  - `assign` refusal as a per-record failure
  - parity between the bulk path and `CreateProductAction`
  - a mixed chunk, and a forced bulk failure that falls back to per-record writes
  - `ProductChangeCapture::suppress()` restoring its state in a `finally`
- [ ] 8.2 Implement:
  - `UpsertProductsData`, the result Data, the `UpsertMode` enum and the event pair
  - `ProductChangeCapture` (scoped)
  - `BulkProductWriter`, sharing the write step with the create action
  - the action flow: validate (including `assign`) → load → preview → bulk or per-record write inside `ProductIndex::deferring()`
- [ ] 8.3 Run vendor/bin/pest; all green

## Group 9: Impex integration
depends on: 8
files: src/Impex/Actions/UpsertProductChunk.php, src/Impex/Actions/ImportProductRow.php (removed), src/Impex/Sources/InlineProductSource.php, src/Impex/Sources/FileProductSource.php, src/Impex/Flows/UpsertProductsFlow.php, src/Impex/Flows/ImportProductsFlow.php, src/Impex/Webhooks/CaptureProductChanges.php, src/Impex/ImpexIntegration.php, tests/ArchTest.php, tests/Feature/Impex/ImportExportTest.php, tests/Feature/Impex/ProductWebhooksTest.php, tests/Feature/Impex/UpsertChunkTest.php
criteria: 30, 31, 32

- [ ] 9.1 Write tests:
  - both flows process per chunk through the action and accept `capture_changes`
  - run results have `total`, `succeeded`, `failed` and `unchanged`; per-row failures are kept with the global row index
  - `CaptureProductChanges` records nothing while suppressed and records by default
  - an arch rule that core (outside `src/Impex`) never references Impex
- [ ] 9.2 Implement:
  - `UpsertProductChunk`, replacing `ImportProductRow` and its per-row event
  - chunked sources and the flow arguments
  - the suppression check in `CaptureProductChanges`
- [ ] 9.3 Run vendor/bin/pest; all green

## Group 10: Atrium screens
depends on: 4, 7
files: src/Atrium/ShowroomPlugin.php, src/Atrium/FlagScreen.php, src/Atrium/Http/Controllers/FlagUiController.php, src/Atrium/Http/Controllers/ProductUiController.php, src/Atrium/Http/Controllers/OwnerUiController.php, resources/views/ui/flags/*, resources/views/ui/products/*, resources/views/ui/owners/*, tests/Feature/Ui/FlagPagesTest.php, tests/Feature/Ui/ProductPagesTest.php, tests/Feature/Ui/OwnerPagesTest.php
criteria: 40, 41

- [ ] 10.1 Write tests:
  - the flag-definitions screen (list, create, edit, delete), with the refusal message shown when a delete is refused
  - product and owner screens: slug read-only, the external_ids map editor, own flags limited to flags that apply to the subject and hidden without `assign`, effective flags read-only with inherited ones marked
  - links use the configured lookup key
- [ ] 10.2 Implement the screens and fields through the existing Atrium screen registry, using only Atrium components.
- [ ] 10.3 Run vendor/bin/pest; all green

## Group 11: Docs and changelog
depends on: 9, 10
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
