# Import / Export via Impex — Shaping Notes

## Scope

Roadmap Milestone 7: bulk import and export of products as Impex flows, syndication feeds per channel, and an entry point for ERP and storefront connectors. Impex stays optional — a suggested dependency — and everything here switches on only when it is installed.

## Decisions

- **Flows registered with Impex** from Showroom's provider (`FlowRegistry::registerMany`, slugs prefixed `showroom:`), so apps get them without config and may still override a slug:
  - `showroom:import-products` — a file (a Showroom asset: `.csv` or `.jsonl`) → products, through `batch()`: one step in the run's history however many rows, per-row idempotency by byte offset, a failure tolerance, per-row retries. Rows go through `CreateProductAction`/`UpdateProductAction`, so validation, uniqueness, versions, completeness and indexing apply exactly as over the API.
  - `showroom:upsert-products` — records passed inline (an inbound Impex channel's JSON body, an ERP push): same row handling, same batch mechanics.
  - `showroom:export-products` — products matching a search (any `ListProductsAction` filters, `scope`/`locales`) to a `.jsonl` or `.csv` file saved as a Showroom asset, written by a `ResumableAction` so a large catalog survives Lambda's time ceiling.
  - `showroom:feed:{name}` — one slug per configured syndication feed: a channel's published products (live versions only, the channel's locales and category tree) to a file asset, optionally pushed to an HTTP endpoint through `Impex::http()` so the delivery lands in the ledger. Feeds have their own slugs so Impex's scheduler can run them by slug.
- **Modes**: `create` (skip existing), `update` (skip missing), `upsert` (default).
- **Formats** (Akeneo conventions):
  - JSONL: one product per line in API shape (`identifier`, `family`, `parent`, `enabled`, `categories`, `values`, `associations`, …).
  - CSV: `identifier`, `family`, `parent`, `enabled`, `categories` (comma-separated), then a column per value slot: `code`, `code-locale`, `code-scope`, `code-locale-scope`; prices `code-USD`; metrics `code` + `code-unit`; multiselect comma-separated; booleans `1`/`0`.
- **Showroom Actions** `StartImportAction` and `StartExportAction` start runs (HTTP + MCP parity); run status, steps and results are read with Impex's own API and MCP tools. Without Impex they answer with a clear error.
- Configuration: `showroom.impex` — `enabled`, batch `chunk`, `allow_failures`, `tries`, and `feeds`.

## Context

- **Visuals:** None.
- **References:** `../impex` docs — flows, replay determinism, `batch()`/`BatchSource`, `ResumableAction`, ledger (`Impex::http`), `FlowRegistry`, testing helpers.
- **Product alignment:** roadmap — "Bulk import/export built on Impex flows; syndication feeds and ERP/storefront connectors".

## Standards Applied

- None indexed yet; see the foundation spec.
