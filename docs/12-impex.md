# Import, export and feeds

Bulk imports, exports and syndication feeds run as [Impex](https://github.com/refactor-circus/impex) flows. Impex is optional:

```bash
composer require refactor-circus/impex
php artisan migrate
```

With it installed (and `showroom.impex.enabled` on), Showroom registers these flows:

| Flow | Does |
|---|---|
| `showroom:import-products` | A `.csv` or `.jsonl` file → products |
| `showroom:upsert-products` | Records passed inline (an ERP push) → products |
| `showroom:export-products` | Products matching a search → a `.jsonl` or `.csv` file |
| `showroom:feed:{name}` | One per configured feed: a channel's published products → a file, optionally pushed to a URL |

Every flow is an ordinary Impex run: its status, steps, failures and result are on Impex's API, MCP tools and dashboard, and Impex can pause, retry, resume and schedule it. Without Impex installed and enabled when the application boots, the start endpoints are not registered (they answer `404`) and the MCP server does not offer the start tools.

## Importing

Rows go through `CreateProductAction` and `UpdateProductAction`, so validation, uniqueness, versions, completeness and indexing apply exactly as over the API.

```http
POST /showroom/imports
Content-Type: multipart/form-data

file=@products.csv
mode=upsert
```

Give the file as exactly one of:

- `file` — an upload,
- `url` — fetched and stored as an asset,
- `asset` — the code of an existing asset (for example one uploaded to S3 with a presigned URL).

The format comes from the extension, or send `format` (`csv` or `jsonl`). `mode` is one of:

| Mode | Existing identifier | New identifier |
|---|---|---|
| `upsert` (default) | updated | created |
| `create` | skipped | created |
| `update` | updated | skipped |

The answer is Impex's run, `202 Accepted`. When it completes, its result counts the rows:

```json
{"total": 1200, "succeeded": 1188, "failed": 12}
```

Each failed row keeps its reason on the run's batch items. By default any number of rows may fail and the run still completes; set `showroom.impex.allow_failures` to a share (`0.02` = 2%) to fail the run past it.

Rows are processed in chunks (`showroom.impex.chunk`) and remembered by position, so a retried or resumed run never imports a row twice.

### JSONL

One product per line, in the API's shape:

```json
{"identifier": "TEE-001", "family": "shirts", "categories": ["tops"], "values": {"name": [{"locale": "en", "scope": null, "data": "Tee"}]}}
```

### CSV

A header row, then one product per row. Columns follow Akeneo's conventions:

| Column | Holds |
|---|---|
| `identifier` | Required |
| `family`, `parent`, `owner` | Codes |
| `enabled` | `1` or `0` |
| `categories` | Comma-separated codes |
| `name` | A value that is neither localizable nor scopable |
| `name-en` | Localizable, in `en` |
| `description-ecommerce` | Scopable, on `ecommerce` |
| `description-en-ecommerce` | Both |
| `price-USD` | A price in one currency (`price-en-ecommerce-USD` for a scoped price) |
| `weight`, `weight-unit` | A metric's amount and unit |

Multiselects are comma-separated option codes; booleans `1` or `0`. An empty cell leaves the value as it is.

## Exporting

```http
POST /showroom/exports
{"family": "shirts", "scope": "ecommerce", "locales": ["en"], "format": "csv", "published": true}
```

- Every [search](05-search.md) filter applies: `search`, `family`, `category`, `owner`, `filters`, `complete`, … plus `scope` and `locales` to keep one channel's values.
- `format`: `jsonl` (default) or `csv`, in the import format — an export imports back unchanged.
- `published`: export the live versions instead of the working copies; unpublished products are left out.
- `code`: the asset code of the file (default `export-{run id}`).

The export pages through the search (`showroom.impex.export_page_size`) as a resumable step, so a large catalog outlives a Lambda time limit. On the database engine it walks the products by identifier and id from the last one written, without counting them, and keeps that key in its resume cursor; a run that yielded before keys were kept resumes from its page number. Each page loads its products' owners, categories, associations and published versions in a fixed number of queries. The result names the asset holding the file, saved under `showroom.impex.export_path` on the media disk:

```json
{"asset": "export-01jq…", "count": 5400, "format": "csv"}
```

## Feeds

A feed is a named export of one channel's published products, with the channel's locales, filed in its category tree:

```php
// config/showroom.php
'impex' => [
    'feeds' => [
        'google' => [
            'channel' => 'ecommerce',
            'format' => 'jsonl',
            'url' => 'https://feeds.example.com/ingest',   // optional
            'ledger_channel' => 'google-feed',            // optional, default: showroom-feeds
        ],
        'partner' => [
            'channel' => 'ecommerce',
            'format' => 'csv',
            'deliver_through' => 'partner-sftp',          // an Impex outbound channel
        ],
    ],
],
```

Each feed is the flow `showroom:feed:google`. With a `url`, the file is `POST`ed to it through `Impex::http()`, so the delivery is recorded in Impex's ledger, and retried up to three times.

With `deliver_through`, the file goes out through that Impex outbound channel instead (`Impex::send()`): the channel's transport (HTTP, file, a partner's SFTP), signing, headers and body policy apply, and a `url` given alongside overrides the channel's endpoint. The delivery is in the ledger either way, and a failed send fails the step, which is retried up to three times.

Schedule a feed with Impex:

```php
// config/impex.php
'schedule' => [
    'showroom:feed:google' => '0 3 * * *',
],
```

Or run it now: `POST /impex/runs {"flow": "showroom:feed:google"}`.

## ERP and storefront connectors

An ERP pushes products by posting to an Impex inbound channel bound to `showroom:upsert-products`:

```php
// config/impex.php
'channels' => [
    'erp' => [
        'direction' => 'inbound',
        'signing_secret' => env('ERP_SECRET'),
        'signature_header' => 'X-Signature',
        'flow' => 'showroom:upsert-products',
        'idempotency_header' => 'X-Request-Id',
    ],
],
```

`POST /impex/channels/erp` with a list of products, or `{"products": [...]}`, in the JSONL shape. The request lands in the ledger, the signature is checked, and a run upserts the records. Storefronts read the live versions directly (`GET /showroom/products/{identifier}/versions/published`) or take a feed.

## Product webhooks

Vendors subscribe to the products and topics they want and are pushed changes,
instead of polling the API. Published products are offered to Impex as a
stream (`showroom.products`); subscribers, endpoints, deliveries, the feed and
the ledger are all Impex's.

```php
// config/showroom.php
'impex' => ['webhooks' => ['enabled' => true]],
```

A vendor, signed in as its OAuth client on Impex's subscriber API:

```http
POST /impex/subscriber/subscriptions
{
  "stream": "showroom.products",
  "topics": ["pricing", "assets"],
  "filter": {"categories": ["power-tools"], "owners": ["acme"]},
  "format": "slice",
  "options": {"channel": "ecommerce", "locales": ["en"]},
  "endpoint": {"url": "https://vendor.example.com/showroom"}
}
```

- **What counts as a change.** Attribute values, family and associations come
  from the published version, so draft edits never leave Showroom; categories,
  owner and linked assets follow the live product. A product is compared topic
  by topic with what subscribers last saw, so re-importing identical data or
  republishing an unchanged product sends nothing.
- **Topics** (`showroom.impex.webhooks.topics`, in a fixed order): `content`
  (everything unclaimed), `pricing` (price attributes), `assets` (linked
  assets, as URL and checksum), `resources` (attribute groups you name) and
  `catalog` (family, parent model, owner, `enabled`, categories,
  associations). Append your own.
- **Filters**: `categories`, `owners`, `families`, `models` — codes, each key
  narrowing, each code widening; a category or owner covers everything beneath
  it. Or list SKUs: `"subjects": ["TEE-1", "TEE-2"]`.
- **Formats**: `thin` (identifier, topics, `href`), `slice` (only the changed
  topics), `full` (the whole published product); add more in
  `showroom.impex.webhooks.formatters`. Values are narrowed to the
  subscription's `options.channel` and `options.locales`.
- **Removals.** Unpublishing, archiving or deleting a product, or moving it out
  of a vendor's categories, reaches that vendor as `removed`. Moving one into
  them sends it whole.
- **What triggers a check.** Every product write that queues an index sync
  (`ProductsQueuedForSync`, which covers changes inherited from models,
  families, categories, owners and channels), product and model deletions, and
  asset links, unlinks and file replacements. Only identifiers are recorded in
  the write; the comparison runs later, on Impex's queue.
- **Starting out.** `POST /impex/subscriber/subscriptions/{id}/export` writes
  every product the subscription covers to one file and moves its cursor past
  them, so a vendor starts from a download rather than millions of webhooks.

Feeds can also leave through any Impex channel: set `deliver_through` on a feed
to send its file the channel's way (an SFTP drop, a signed HTTP upload); see
[Feeds](#feeds).

## Polling, while vendors move over

`GET /showroom/products?updated_since=2026-10-01T00:00:00Z` lists products
where anything they show changed since then, inherited changes included, and
`GET /showroom/products/{identifier}` answers `304` to a matching
`If-None-Match`. Polling still costs a request per page per vendor; webhooks
cost nothing until something changes.

## From PHP

```php
use RefactorCircus\Showroom\Domains\Transfer\Actions\StartExportAction;
use RefactorCircus\Showroom\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Showroom\Domains\Transfer\Data\StartExportData;
use RefactorCircus\Showroom\Domains\Transfer\Data\StartImportData;

$run = app(StartImportAction::class)->execute(StartImportData::from(['asset' => 'supplier-2026-09', 'mode' => 'update']));

$run = app(StartExportAction::class)->execute(StartExportData::from(['family' => 'shirts', 'format' => 'csv']));
```

Both return the Impex `Run`. MCP agents use `start-import-tool` and `start-export-tool`, then Impex's tools to follow the run.

## Dashboard

**Catalog → Import & export** uploads a file to import, starts an export, and lists the latest Showroom runs with their results, linked to the Impex run page and the exported asset.
