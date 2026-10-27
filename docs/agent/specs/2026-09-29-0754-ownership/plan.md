# Dynamic Hierarchical Ownership — Plan

1. Save spec documentation (`shape.md`, this plan).
2. Migration `2026_01_01_000004_create_showroom_owner_tables.php`: owner types (+ parent-type pivot), owners (materialized path, depth), `owner_id` on products and product models.
3. Models `OwnerType`, `Owner` (chain, subtree scope, path helpers), factories, model events; `owner()` on `Product`/`ProductModel`, `effectiveOwner()`, `rootModel()`.
4. Actions (10) with events; concerns `WritesOwnerTypes`, `PlacesOwners` (rules, cycles, subtree path rewrite), `AssignsOwners`; owner on product/model create/update with re-indexing.
5. Search: document `owner`/`owners`; `owner` query on database, Elasticsearch and Scout engines.
6. HTTP, MCP (10 tools + owner params), policies, config.
7. Atrium: Owners (chain breadcrumb, children, move) and owner types pages; owner fields on products and root models; products filter by owner; search.
8. Tests: API (rules, chains, moves, cycles, assignment, search, deletes), MCP, UI, engines, counts.
9. Docs (`06-ownership.md`, API, MCP, dashboard, search, install), README, CHANGELOG, Boost skill.

Verification: `composer test`.
