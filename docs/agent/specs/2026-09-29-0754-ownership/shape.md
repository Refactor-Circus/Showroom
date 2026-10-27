# Dynamic Hierarchical Ownership — Shaping Notes

## Scope

Roadmap Milestone 1, last item: configurable owner types (manufacturer, vendor, brand, series, …) in arbitrary chains and depths — vendor → series → product, vendor → vendor → product, manufacturer → vendor → product — with products and product models assigned to an owner. Same surfaces: Actions → HTTP, MCP, Atrium, events, search, tests.

## Decisions

- **Owner types** are runtime records: `code` (immutable), `labels`, `sort_order`, and three rules:
  - `parent_types`: which owner types may be a parent — `null` for any type, a list to restrict (`[]` = none). Stored in a pivot with FKs.
  - `can_be_root`: whether an owner of this type may have no parent.
  - `owns_products`: whether products and product models may be assigned to it.
  Rules are checked on writes; changing them does not rewrite existing owners.
- **Owners**: `code` (globally unique, immutable — addressed by code like everything else), owner type (immutable), `labels`, optional `parent`. Moving an owner (changing parent) is allowed; cycles are refused.
- **Materialized path** (`/id/id/…/`) and `depth` on each owner, so "everything under Acme" is one prefix query at any depth. Rewritten for the subtree on a move.
- **Assignment**: one owner per simple product and per root product model — the deepest owner in the chain (the series, not the vendor). Sub-models and variant products take their root model's owner and cannot set their own.
- **Search**: the indexed document carries `owner` (code) and `owners` (the whole chain). The product query gains `owner`, matching products owned by that owner or anything beneath it, on all three engines. Moves re-index the subtree's products.
- **Deleting**: an owner with child owners or products/models is refused (409); an owner type with owners is refused.
- Media at any ownership level comes with milestone 3.

## Context

- **Visuals:** None.
- **References:** earlier slices (Actions/HTTP/MCP/Atrium pattern, search engines).
- **Product alignment:** mission — "Dynamic hierarchical ownership — any chain of owner types, any depth".

## Standards Applied

- None indexed yet; see the foundation spec.
