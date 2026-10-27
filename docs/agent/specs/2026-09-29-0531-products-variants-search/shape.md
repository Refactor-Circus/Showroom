# Products, Product Models, Variants & Search — Shaping Notes

## Scope

Roadmap Milestone 1, final slice: products with runtime attribute values, product models and variant products (family variants, up to two levels of axes), uniqueness, and pluggable product search. Same surfaces as before: Actions → HTTP, MCP, Atrium, events, tests.

## Decisions

- **Value storage: JSON column** on products and product models (chosen over EAV — see the comparison in the conversation: whole-product reads/writes, bulk import, locale/channel growth, inheritance and versioning favour JSON; filtering is delegated to a search engine).
- **Storage shape:** `{attribute: {channel|<all_channels>: {locale|<all_locales>: data}}}` (Akeneo "raw values"), so the database engine can filter with Laravel JSON paths on MySQL, PostgreSQL and SQLite.
- **API shape:** Akeneo standard format — `{"color": [{"locale": null, "scope": null, "data": "red"}]}`. PATCH replaces only the slots sent; `data: null` clears one.
- **Uniqueness:** `showroom_product_unique_values (attribute_id, value_hash, product_id)` with a unique index. Unique attributes cannot be localizable or scopable, and cannot sit on product-model levels.
- **Family variants:** belong to a family; one or two levels, each with 1–5 axes (select, boolean or metric; not localizable/scopable) and the attributes set at that level. Attributes on no level are common, set on the root product model. Levels are fixed once product models use the variant.
- **Product models:** `code` (immutable), family variant, optional parent (sub-model at level 1 of a two-level variant), values for their level only.
- **Products:** `identifier` (immutable), optional family, optional parent product model (variant product), `enabled`, own values. Variant products take the family of their family variant, set only last-level attributes, must fill every axis, and differ from their siblings on the axes. API returns own + inherited values.
- **Search (user's choice of engine):** Showroom `SearchEngine` contract with three drivers, selected by `showroom.search.engine`:
  - `database` — default, no dependencies, JSON-path filters.
  - `elasticsearch` — native, built on `refactor-circus/stretch` (suggested dependency): mappings, bulk indexing, bool filters, facets.
  - `scout` — any Laravel Scout engine (suggested dependency); comparisons and `in`/`not_in` pass through Scout's builder, `empty`/`not_empty` refused with a clear error.
- Indexing on every product write (queued when `showroom.search.queue` is set), cascaded to variants when a product model changes; `showroom:search:reindex` rebuilds.
- Products list/search is page-based (engines count totals), unlike the cursor listings elsewhere.
- Deleting an attribute or option purges stored values in a queued job; attributes used as variant axes cannot be deleted.
- Completeness (required attributes) is not enforced on save — products may be incomplete; completeness lands in milestone 6.
- Dependencies: `refactor-circus/stretch` (VCS, dev-main) and `laravel/scout` ^11 in `require-dev` + `suggest`, guarded by `class_exists`.

## Context

- **Visuals:** None.
- **References:** `../stretch` (ClientContract, Stretch facade/builder, bulk, index management); attribute and family slices here.
- **Product alignment:** mission — products, product models and variants (variant axes); Laravel-native, S3/Vapor-friendly (queued indexing, no local state).

## Standards Applied

- None indexed yet; see the foundation spec.
