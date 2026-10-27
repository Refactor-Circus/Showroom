# Taxonomy — Shaping Notes

## Scope

Roadmap Milestone 2: hierarchical category trees (several trees side by side — "Master catalog", "Web navigation", "Print catalog") and assigning products and product models to categories. Same surfaces as before: Actions → HTTP, MCP, Atrium, events, search, tests.

## Decisions

- **Categories** are one table: a root category *is* a tree. `code` (globally unique, immutable), `labels`, `parent`, `sort_order` among siblings. Materialized `path` + `depth`, as for owners — the path logic is shared (`HasPath` model concern, `MovesInTree` action concern).
- **Moves** (changing `parent`, including to root or into another tree) carry the subtree along; cycles refused; products beneath re-indexed.
- **Deleting**: a category with children is refused (delete bottom-up, as for owners). A leaf with products is deleted and its assignments removed, and those products re-indexed.
- **Assignment**: many-to-many, to products and to product models (any level). `categories` on create/update is the full list of codes, replaced on update. A variant's categories are its own plus every ancestor model's — like values, inherited.
- **Search**: the document carries `categories` (assigned codes, inherited included) and `category_tree` (those plus all their ancestors). The product query gains `category`, matching products in that category **or any category beneath it**, on all three engines. `categories` is returned by the product API.
- No per-tree channel binding yet — channels come in milestone 4.

## Context

- **Visuals:** None.
- **References:** ownership slice (materialized paths, moves, subtree search).
- **Product alignment:** mission — "Full taxonomy — hierarchical category/classification trees".

## Standards Applied

- None indexed yet; see the foundation spec.
