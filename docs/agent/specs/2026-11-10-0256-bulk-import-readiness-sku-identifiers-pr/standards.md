# Standards for Bulk import readiness: SKU identifiers, slugs, external IDs, flags, batch upsert

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| actions/actions-events | vendor/refactor-circus/standards/standards/actions/actions-events.md | refactor-circus/standards@dev-main | New `UpsertProductsAction` and Flag CRUD actions; changed product/owner/owner-type create and update actions |
| data/data-objects | vendor/refactor-circus/standards/standards/data/data-objects.md | refactor-circus/standards@dev-main | New `UpsertProductsData` and Flag Data objects; `external_ids`/`flags` on product and owner Data; configurable identifier rule |
| models/models | vendor/refactor-circus/standards/standards/models/models.md | refactor-circus/standards@dev-main | New `ExternalIdModel` and `FlagModel`; `slug` on `ProductModel` and `OwnerModel`; configurable table names, policies |
| models/migrations | vendor/refactor-circus/standards/standards/models/migrations.md | refactor-circus/standards@dev-main | New external-id, flag and flag-assignment tables; slug columns with backfill |
| models/stored-identifiers | vendor/refactor-circus/standards/standards/models/stored-identifiers.md | refactor-circus/standards@dev-main | Slug becomes the route key; inputs keep referencing by identifier or code; external ids |
| architecture/configuration | vendor/refactor-circus/standards/standards/architecture/configuration.md | refactor-circus/standards@dev-main | `showroom.products.identifier_pattern`, upsert chunk size, new `showroom.tables` and `showroom.policies` entries |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | The batch action lives in core; the Impex flows and webhook capture stay in the optional integration |
| http/resources | vendor/refactor-circus/standards/standards/http/resources.md | refactor-circus/standards@dev-main | `slug`, `external_ids`, `flags`, `effective_flags` on product and owner resources; new `FlagResource` |
| http/routes | vendor/refactor-circus/standards/standards/http/routes.md | refactor-circus/standards@dev-main | Slug binding on product, workflow and owner routes; new flag routes |
| http/request-persist | vendor/refactor-circus/standards/standards/http/request-persist.md | refactor-circus/standards@dev-main | Flag requests; product and owner requests accept `external_ids` and `flags` |
| http/authorization | vendor/refactor-circus/standards/standards/http/authorization.md | refactor-circus/standards@dev-main | `FlagPolicy`; the batch upsert authorizes as create/update would |
| testing/test-layers | vendor/refactor-circus/standards/standards/testing/test-layers.md | refactor-circus/standards@dev-main | Behaviour tested at the actions; HTTP, MCP and Impex surfaces test their wiring |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | New actions, Data, models and requests must pass the arch preset |
| showroom/atrium-screens | docs/agent/standards/showroom/atrium-screens.md | showroom (local) | Flag screen; slug, external ids and flags on product and owner screens |
