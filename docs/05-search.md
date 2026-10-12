# Search

Products are listed and searched through a search engine you choose with `showroom.search.engine`. Every surface — `ListProductsAction`, `GET /showroom/products`, `list-products-tool`, the dashboard — goes through it.

| Engine | Needs | Filters | Scale |
|---|---|---|---|
| `database` | nothing | all operators on every type but price | thousands to tens of thousands of products |
| `scout` | `laravel/scout` + a Scout engine | `=`, `!=`, ranges, `in`, `not_in` — as far as the Scout engine supports them | engine-dependent |

The database engine reads the products table directly. Filters, sorting and paging use indexes, but a `search` term is matched as `LIKE '%term%'` against the identifier and every level's JSON `values`, which no index can serve: each search reads every matching row. Above some tens of thousands of products, or when free-text search is common, use Scout (or your own engine) instead. Exports on the database engine walk the catalogue by key and never count it, so they stay cheap at any size.

Left unset (`null`, the default), the engine is `scout` when laravel/scout is installed and `database` otherwise. You can also bind your own class implementing `RefactorCircus\Showroom\Domains\Search\Contracts\SearchEngine`, and name it in the config. Its `search()` takes a `Search\Data\ProductQuery`, built from the validated `ListProductsData` by `ListProductsData::query()`: each of its `filters` is a `Search\Data\Filter` (`attribute`, `operator`, `value`, `locale`, `scope`; `values()` lists the value for `in`/`not_in`) and `complete` a `Search\Data\Complete`. Showroom no longer ships an Elasticsearch engine: use a Scout driver for Elasticsearch, or your own engine.

## Querying

```http
GET /showroom/products?search=tee&family=shirts&enabled=1
    &filters[0][attribute]=color&filters[0][operator]=in&filters[0][value][]=red&filters[0][value][]=blue
    &filters[1][attribute]=weight&filters[1][operator]=>=&filters[1][value]=100
    &facets[]=color&sort=-updated_at&page=1&per_page=25
```

- `search`: full text over the identifier and text, textarea and select values (inherited ones included).
- `owner`: products owned by this owner code or anything beneath it.
- `category`: products filed in this category code or any category beneath it.
- `status`: `draft`, `in_review`, `approved` or `archived`; `published`: with or without a live version.
- `complete`: `{scope, locale?, min}` — see [Workflow](11-workflow.md).
- `updated_since`: products where anything they show changed at or after this moment (ISO 8601, or any date Laravel's `date` rule accepts) — see [Changed since](#changed-since).
- `filters`: all must hold. Operators: `=`, `!=`, `in`, `not_in`, `>`, `>=`, `<`, `<=`, `empty`, `not_empty`. Add `locale` / `scope` for localizable / scopable attributes. Multiselect `=` means "contains". Metric filters compare the amount.
- `facets`: attribute codes to count values of across every match, returned as `facets: {"color": {"red": 12, "blue": 3}}`. Only a custom engine that counts facets fills them; the bundled engines return none.
- `sort`: `identifier`, `created_at` or `updated_at`, `-` for descending.
- Pagination is page-numbered, with `meta.total` and `meta.last_page`.

A filter the engine cannot run answers `422` with a message.

## Changed since

`updated_since` compares a product's `changed_at`: when anything it shows last changed. That is its own values, family, owner, `enabled` flag and transitions, and also what `updated_at` never sees — a refiling, an association, an asset linked or replaced, and everything it inherits from its product model, family, categories, owner or channel. `changed_at` is set by `SyncProductIndex`, which every such change already queues, so it costs nothing extra on the write.

The database engine queries `showroom_products.changed_at`, and Scout passes `changed_at >= <ISO 8601 string>` to its engine (declare `changed_at` filterable, as for any Scout filter; the engine must support a range on it).

Because `changed_at` is stamped when the sync job runs, a change is never missed: a client that asked "since T" while the job was still queued sees the product on its next call, stamped after T. Products also carry `changed_at` in their API representation, so a client can keep the latest one it saw as its next `updated_since`.

## Keeping the index in step

Engines with an index (`scout`, or your own) are fed by a queued job, `SyncProductIndex`, dispatched after each write commits. It carries product ids only. Changing a product model re-indexes its variants. Before the jobs are dispatched, `ProductIndex::queue()` fires `RefactorCircus\Showroom\Domains\Search\Events\ProductsQueuedForSync` with the product ids, inside the write's transaction, so a listener can follow every product whose presentation may have changed — its own edits, a transition, or a change it inherits from a model, family, category, owner or channel. Product webhooks are built on it.

A change that reaches many products — a channel's code, locales, currencies or category tree (its labels alone change no product), a family's attributes, an owner or category moving in its tree — does not list them inside its own request: it queues one `QueueProductSyncs` job after it commits, which walks the products in chunks of 500 and queues their syncs. Deleting a category lists its products a chunk at a time before the assignments go.

Code that writes many products in one process can hold their syncs back and send them together, one job per 500 products instead of one per write:

```php
app(ProductIndex::class)->deferring(function (): void {
    foreach ($rows as $row) {
        app(UpdateProductAction::class)->execute(...);
    }
});
```

Choose the queue with:

```php
'search' => [
    'queue' => ['connection' => 'redis', 'queue' => 'search'],
],
```

Rebuild the whole index at any time:

```bash
php artisan showroom:search:reindex
```

The indexed document holds the identifier, family, parent model, owner and owner chain, categories and their ancestors, status, published flag, completeness per channel and locale, enabled flag, dates, a `text` field for full text, and `values` in storage shape — `values.color.<all_channels>.<all_locales>` — with all inherited values merged.

## Scout

```bash
composer require laravel/scout meilisearch/meilisearch-php
```

```php
'search' => [
    'engine' => 'scout',
    'scout' => ['index' => 'showroom_products'],
],
```

Scout's own `scout.driver` picks the engine. Declare the fields you filter on as filterable in the engine's index settings (for Meilisearch, `family`, `enabled`, `parent` and each `values.<code>.<all_channels>.<all_locales>` you filter by), as Scout expects.
