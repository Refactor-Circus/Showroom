# Media

Assets are files — images, manuals, logos, videos — stored on a Laravel filesystem disk and linked to products, product models and owners under a role.

## Storage

```php
// config/showroom.php
'media' => [
    'disk' => 's3',             // null: the app's default disk
    'path' => 'showroom/assets',
    'max_kilobytes' => 51200,
    'mime_types' => null,       // or ['image/jpeg', 'image/png', 'application/pdf']
    'temporary_urls' => null,   // minutes, for private buckets
    'delete_files' => true,
    'download_timeout' => 30,
],
```

Showroom never assumes a local disk that outlives the request, so it runs on Vapor or Lambda with S3. Files are streamed on the way in and while being checksummed, never held whole in memory. Each asset records its own disk, so changing the configured disk later does not strand existing files.

## Adding assets

`POST /showroom/assets` takes exactly one source:

| Source | For |
|---|---|
| `file` — multipart upload | the HTTP API |
| `path` — a file already on the asset disk | uploads sent straight to S3 with a presigned URL, so no file passes through the app server |
| `url` — an http(s) URL | fetched server-side and streamed to the disk; how MCP agents add media |

```http
POST /showroom/assets
{"url": "https://cdn.example.com/tee-front.jpg", "labels": {"en": "Front view"}}
```

- `code` is optional; it is generated from the file name when omitted, and never changes.
- The file name is slugged and each file gets its own folder, so names never collide.
- Size and MIME limits apply to every source. A fetched file that breaks them is removed; an adopted `path` is left where it was.
- The record holds `filename`, `mime_type`, `size` and a SHA-256 `checksum`, and the API returns a `url`: a plain URL, or a signed one when `temporary_urls` is set.

Replacing the file — `file`, `path` or `url` on update — keeps the code and every link, and deletes the old file. Multipart replacements go as `POST /showroom/assets/{code}` with `_method=PATCH`, because PHP only parses multipart bodies on POST.

## Linking

```http
POST /showroom/assets/tee-front/links
{"type": "product_model", "target": "classic-tee", "role": "image", "sort_order": 1}
```

- `type` is `product` (target: identifier), `product_model` or `owner` (target: code). An owner can be any level of an ownership chain — a brand logo, a series banner.
- `role` is a free code — `image`, `manual`, `logo` — so one asset can serve several roles on one record. The default is `media`.
- `DELETE /showroom/assets/{code}/links` with `type` and `target` unlinks it — in one `role`, or all of them without one.
- Product, product model and owner responses include their `assets`. A variant product lists its own first, then its models' (`"inherited": true`).
- List assets by the record they are linked to: `GET /showroom/assets?product=TEE-1&role=image`, or by type: `?type=image/*`.

## Deleting

Deleting an asset removes its links and — unless `delete_files` is off — its file. Deleting a product, product model or owner removes its links.

## Actions

`ListAssetsAction`, `ShowAssetAction`, `CreateAssetAction`, `UpdateAssetAction`, `DeleteAssetAction`, `AttachAssetAction`, `DetachAssetAction`.
