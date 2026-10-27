# Media & Assets — Shaping Notes

## Scope

Roadmap Milestone 3: asset management on a configurable filesystem disk (S3-ready, no local-disk assumptions — Vapor/Lambda friendly), and attaching assets to any ownership level, product model or product. Same surfaces as before: Actions → HTTP, MCP, Atrium, events, tests.

## Decisions

- **Asset** record: `code` (unique, immutable; generated from the filename when omitted), `labels` (alt text / titles per locale), `disk`, `path`, `filename`, `mime_type`, `size`, `checksum` (sha256). The file lives on `showroom.media.disk` (default: the app's default disk) under `showroom.media.path`.
- **Three ways in**, all through one `CreateAssetAction`:
  - `file` — a multipart upload (HTTP API, dashboard);
  - `path` — an object already on the disk, e.g. uploaded straight to S3 with a presigned URL (the Vapor way, no file through the app server);
  - `url` — fetched server-side and streamed to the disk (how MCP agents add media, since MCP carries no files).
  Files are streamed, never buffered whole in memory. Size and MIME type limits are configurable.
- **Replacing** a file on update (`file`/`path`/`url`) keeps the asset's code and links.
- **Links**: polymorphic `showroom_asset_links` (asset, linkable type + id, `role`, `sort_order`) to products, product models and owners. `role` is a free code — `image`, `manual`, `logo` — so one asset can serve several roles. `AttachAssetAction` / `DetachAssetAction`.
- **Inheritance**: variant products show their product models' assets after their own, as with values and categories. Owner assets (logos, brand imagery) are not pushed down to products.
- **URLs**: `Storage::url()`, or temporary URLs when `showroom.media.temporary_urls` sets a lifetime (private S3 buckets).
- **Deleting** an asset removes its links and — unless `showroom.media.delete_files` is off — its file. Deleting a product, model or owner removes its links.
- Links carry their own morph aliases (`showroom_product`, `showroom_product_model`, `showroom_owner`), registered without enforcing a morph map on the host app.

## Context

- **Visuals:** None.
- **References:** earlier slices; tech stack note — "media on a configurable filesystem disk (S3), no local disk".
- **Product alignment:** mission — "Media management — assets can be attached at any level of the ownership chain (and to products/variants)".

## Standards Applied

- None indexed yet; see the foundation spec.
