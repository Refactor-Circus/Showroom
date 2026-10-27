# Products, Product Models, Variants & Search — Plan

1. Save spec documentation (`shape.md`, this plan).
2. Dependencies: `refactor-circus/stretch` (VCS) and `laravel/scout` ^11 in `require-dev` + `suggest`.
3. Migration `2026_01_01_000003_create_showroom_product_tables.php`: family variants (+ attribute placement), product models, products, unique values.
4. Models `FamilyVariant`, `ProductModel`, `Product` (+ `HasValues`, factories, model events); `Values` shape converter; `ValueValidator`; `UniqueValues`.
5. Search: `Contracts\SearchEngine`; `ProductQuery`, `Filter`, `SearchResults`, `ProductDocument`, `ProductPage`; engines `DatabaseEngine`, `ElasticsearchEngine` (Stretch), `ScoutEngine` (+ `SearchableProduct`); `ProductIndex`, `SyncProductIndex` job, `showroom:search:reindex`; engine resolved from `showroom.search.engine`.
6. Actions (15) with events: family variants, product models, products (`ListProductsAction` searches). Guards added to attribute, option and family Actions; `PurgeAttributeValues` job.
7. HTTP resources/requests/controllers/routes; MCP requests/tools in `ShowroomServer::TOOLS`; policies in config.
8. Atrium: Products, Product models pages; family variants on the family page; value editor partial; search.
9. Tests: API (variants, products), search engines (database real; Elasticsearch via mocked Stretch client; Scout via recording engine), MCP, UI, counts.
10. Docs (`04-products.md`, `05-search.md`, API, MCP, dashboard, config, install), README, CHANGELOG, Boost skill.

Verification: `composer test`; workbench pages load.
