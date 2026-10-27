# Taxonomy — Plan

1. Save spec documentation (`shape.md`, this plan).
2. Extract the materialized-path logic from ownership: `Models\Concerns\HasPath` (chain, subtree scope, path helpers) and `Actions\Concerns\MovesInTree` (placement and subtree rewrite); owners refactored onto them.
3. Migration `2026_01_01_000005_create_showroom_category_tables.php`: categories, product and product-model pivots.
4. `Category` model, factory, model events; `categories()` / `allCategories()` on products and models.
5. Actions (5) with events; `AssignsCategories` on product and product-model create/update; re-index on moves, deletes and model category changes.
6. Search: `categories` and `category_tree` in the document; `category` query on all engines.
7. HTTP, MCP (5 tools + `categories` / `category` params), policy, config.
8. Atrium: category trees, nested branch view, moves; category fields on products and models; product filter; search. Dashboard payloads validated against Action rules.
9. Tests, docs (`07-taxonomy.md` and cross-references), README, CHANGELOG, Boost skill.

Verification: `composer test`.
