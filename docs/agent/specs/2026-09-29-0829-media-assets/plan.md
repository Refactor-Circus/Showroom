# Media & Assets — Plan

1. Save spec documentation (`shape.md`, this plan).
2. Migration `2026_01_01_000006_create_showroom_asset_tables.php`: assets and polymorphic asset links.
3. `Asset` model, factory, model events; `HasAssets` on products, product models and owners; `allAssets()` inheritance for variants; morph aliases registered without enforcing a map.
4. `Media\AssetStorage` (streamed upload, adopt disk path, fetch URL, checksum, URLs, delete), `Media\AssetLinks` (resolve, attach, detach, forget), `media` config.
5. Actions (7) with events; `StoresAssetFiles` concern (one source, limits); link cleanup when products, models and owners are deleted.
6. HTTP (incl. links endpoints), MCP (7 tools), policy, `assets` on product, model and owner resources.
7. Atrium: asset library grid, asset page (preview, replace, links), media cards with upload-and-link on product, model and owner pages; search.
8. Tests (Storage::fake, Http::fake), docs (`08-media.md`, API, MCP, dashboard, config, install), README, CHANGELOG, Boost skill.

Verification: `composer test`.
