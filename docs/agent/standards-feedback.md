# Standards Feedback

Every time work has to break a standard, add a row. `/ring-master-discover-standards` reads this log, so changes to the shared standards are based on evidence.

| Date | Spec | Standard | Deviation | Why |
| --- | --- | --- | --- | --- |
| 2026-11-10 | 2026-11-10-0256-bulk-import-readiness-sku-identifiers-pr | models/stored-identifiers, http/routes, http/resources | Products and owners are looked up by their ULID `id` by default, not by slug: the `{product}` and `{owner}` route bindings, Atrium links, the MCP `product`/`owner` arguments and the show, update, delete, transition, versions and revert requests resolve through `Support\RecordLookup`. `showroom.lookup.products` and `showroom.lookup.owners` switch the key to `slug`, and a call's `by` (`?by=`, MCP `by`) overrides it. Resources expose `id` alongside `slug`. Inputs that reference other records (`parent`, `owner`, associations, filters) keep the identifier or code. | D-001: the user wants every product and owner lookup to use the ULID by default, configurable and overridable per call. D-010 limits the keys to `id` and `slug`, so a SKU with `/` or spaces never reaches a URL. |
