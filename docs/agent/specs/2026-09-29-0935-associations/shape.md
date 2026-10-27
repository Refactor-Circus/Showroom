# Associations, Bundles & Kits — Shaping Notes

## Scope

Roadmap Milestone 5: relations between products — cross-sell, up-sell, accessories, replacement and compatible parts — and bundles/kits made of other products in quantities. Same surfaces: Actions → HTTP, MCP, Atrium, events, tests.

## Decisions

- **Association types** are runtime records: `code` (immutable), `labels`, and two immutable flags:
  - `is_two_way`: an association also exists the other way round (compatible parts, replacements) — kept in sync on both sides automatically;
  - `is_quantified`: each associated item carries a quantity — the type for bundles and kits.
  A type cannot be both. A type in use cannot be deleted (409).
- **Associations** link a product or product model (source) to products and product models (targets), per type; quantified ones carry an integer `quantity` ≥ 1. One table, `showroom_associations`, polymorphic on both ends (the morph aliases already registered for assets).
- **API shape** (Akeneo's):
  ```json
  "associations": {"cross_sell": {"products": ["SKU-2"], "product_models": ["tee"]}},
  "quantified_associations": {"bundle": {"products": [{"identifier": "SKU-3", "quantity": 2}], "product_models": []}}
  ```
  On update, each type sent replaces that type's targets; types not sent are kept. A record cannot be associated with itself.
- **Inheritance**: variant products show their product models' associations merged with their own, as with values, categories and assets; for quantified types the variant's own quantity wins.
- **Deleting** a product or product model removes every association from and to it.
- Bundle pricing and stock are out of scope — Showroom holds the composition; commerce systems compute from it.

## Context

- **Visuals:** None.
- **References:** asset links (polymorphic links, cleanup on delete); product/model Actions.
- **Product alignment:** roadmap — "Cross-sell, up-sell, accessories, replacement/compatible parts; bundles / kits".

## Standards Applied

- None indexed yet; see the foundation spec.
