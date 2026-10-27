# Product Roadmap

## Phase 1: MVP

Build in roughly this order; each milestone ships with Actions, HTTP API, MCP tools, Atrium pages, and tests.

1. **Catalog core**
   - Attributes (typed), attribute groups, attribute options
   - Families (attribute sets) with required/optional attributes
   - Products, product models, and variants (variant axes)
   - Dynamic hierarchical ownership: configurable owner types (manufacturer, vendor, brand, series, …) in arbitrary chains and depths
2. **Taxonomy**
   - Hierarchical category trees (multiple trees supported)
   - Product ↔ category assignment
3. **Media / assets**
   - Asset management on a configurable disk (S3-ready, no local disk assumptions)
   - Assets attachable to any ownership level, product, or variant
4. **Channels & locales**
   - Localizable and channel-scoped attribute values
   - Translations of labels and values
5. **Associations & relations**
   - Cross-sell, up-sell, accessories, replacement/compatible parts
   - Bundles / kits
6. **Completeness & workflow**
   - Completeness scoring per channel + locale
   - Enrichment workflow, approval and publishing states
   - Versioning and audit history
7. **Import / export via Impex**
   - Bulk import/export built on Impex flows
   - Syndication feeds and ERP/storefront connectors
8. **Surfaces** (cross-cutting, every milestone)
   - Full HTTP API
   - MCP server with tool parity (same Actions as HTTP)
   - Atrium dashboard pages, widgets, and search
   - Cortex integration (optional)

## Phase 2: Post-Launch

To be determined.
