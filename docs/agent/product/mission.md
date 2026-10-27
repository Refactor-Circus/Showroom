# Product Mission

## Problem

Laravel applications need a Product Information Management (PIM) system: a single source of truth for product data — attributes, variants, taxonomy, ownership, media, and channel-ready content. Showroom is that PIM, delivered as a Laravel package rather than a separate platform.

## Target Users

- **Laravel developers** embedding PIM capabilities into their own applications, storefronts, and integrations.
- **Internal catalog/product teams** (dogfooded internally) who enrich, organize, and publish product data through the dashboard, API, and AI agents.

## Solution

- **Laravel-native package** following the `refactor-circus/impex` package as the standards template: Actions shared by every surface, service-provider wiring, publishable config/migrations, Atrium dashboard, Cortex integration.
- **Full HTTP API with MCP parity** — every operation available over HTTP is available as an MCP tool, both calling the same Action.
- **Dynamic data model** — attributes, attribute groups, and families (attribute sets) are defined at runtime, not per-product-type migrations.
- **Full taxonomy** — hierarchical category/classification trees.
- **Dynamic hierarchical ownership** — any chain of owner types, any depth, e.g.:
  - vendor → series → product
  - vendor → product
  - vendor → vendor → series → product
  - vendor → vendor → product
  - manufacturer → vendor → product
- **Media management** — assets can be attached at any level of the ownership chain (and to products/variants).
- **Industry-standard PIM terminology** — attributes, attribute groups, families, product models/variants, categories/category trees, channels, locales, completeness, associations, assets.
