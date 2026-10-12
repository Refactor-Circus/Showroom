<div align="center">
    <img src="art/icon.png" width="160" alt="Showroom icon">
    <h1>Showroom</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/refactor-circus/showroom"><img src="https://img.shields.io/packagist/v/refactor-circus/showroom.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/refactor-circus/showroom"><img src="https://img.shields.io/packagist/php-v/refactor-circus/showroom.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/refactor-circus/showroom"><img src="https://badge.laravel.cloud/badge/refactor-circus/showroom?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/refactor-circus/showroom/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/refactor-circus/showroom/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/refactor-circus/showroom"><img src="https://img.shields.io/packagist/dt/refactor-circus/showroom.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Showroom is a product information management (PIM) system delivered as a Laravel package: a single source of truth for product data — attributes, families, variants, taxonomy, ownership, media and channel-ready content — inside your own application.

Every operation is one Action, reachable over a JSON API, an MCP server for agents, and an [Atrium](https://github.com/Refactor-Circus/Atrium) dashboard.

> **Status:** early development. The catalog core — attributes, families, products, product models and variants, pluggable search and hierarchical ownership — category trees, media, channels and locales, associations and bundles, completeness, workflow and versioning have landed; the rest of the [roadmap](#roadmap) follows.

## Installation

```bash
composer require refactor-circus/showroom

php artisan vendor:publish --tag="showroom-migrations"
php artisan migrate
```

Optionally publish the config, views or translations:

```bash
php artisan vendor:publish --tag="showroom-config"
php artisan vendor:publish --tag="showroom-views"
php artisan vendor:publish --tag="showroom-lang"
```

`--tag="showroom"` publishes all of them at once.

Every table can be renamed before migrating: publish the config and set `showroom.tables` (keys such as `products`, `product_models`, `categories`; a published config that lists only some keeps the defaults for the rest). Migrations, models, validation rules and queries all read it.

## Attributes

Attributes are the typed characteristics products are described with, defined at runtime rather than in migrations. Every record is addressed by its **code**, which never changes once created; an attribute's **type** never changes either.

| Type | Settings |
|---|---|
| `text` | `max_length`, `regex` |
| `textarea` | `max_length`, `rich_text` |
| `number` | `min`, `max` |
| `decimal` | `min`, `max`, `decimals` |
| `boolean` | — |
| `date` | `min`, `max` |
| `select`, `multiselect` | — (take options) |
| `price` | `currencies`, `decimals` |
| `metric` | `metric_family`, `default_unit` (both required) |

- **Attribute groups** organise attributes into sections (an attribute belongs to at most one). A group that still holds attributes cannot be deleted.
- **Options** belong to select and multiselect attributes only; their codes are unique within the attribute.
- **Labels** are keyed by locale: `{"en": "Color", "fr": "Couleur"}`.
- `is_unique` applies to text, number, decimal and date attributes. `is_localizable` values name an existing locale and `is_scopable` values an existing channel.

```php
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeAction;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeOptionAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeData;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeOptionData;

$color = app(CreateAttributeAction::class)->execute(CreateAttributeData::from([
    'code' => 'color',
    'type' => 'select',
    'labels' => ['en' => 'Color'],
]));

app(CreateAttributeOptionAction::class)->execute($color, CreateAttributeOptionData::from(['code' => 'red', 'labels' => ['en' => 'Red']]));
```

See [docs/02-attributes.md](docs/02-attributes.md).

## Families

A family (attribute set) is a kind of product: which attributes it has, which are required, and which text attribute names its products.

```php
use RefactorCircus\Showroom\Domains\Family\Actions\CreateFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Data\CreateFamilyData;

app(CreateFamilyAction::class)->execute(CreateFamilyData::from([
    'code' => 'shoes',
    'labels' => ['en' => 'Shoes'],
    'attributes' => [
        ['attribute' => 'name', 'is_required' => true],
        ['attribute' => 'size', 'is_required' => true],
        ['attribute' => 'color'],
    ],
    'label_attribute' => 'name',
]));
```

On update, `attributes` replaces the whole list. See [docs/03-families.md](docs/03-families.md).

## Products and variants

Products are identified by their `identifier` (SKU) and hold values as lists of `{locale, scope, data}` slots per attribute. Values are stored as JSON on the product; unique attributes are enforced by a database index.

```php
use RefactorCircus\Showroom\Domains\Product\Actions\CreateProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;

app(CreateProductAction::class)->execute(CreateProductData::from([
    'identifier' => 'TEE-001',
    'family' => 'shirts',
    'values' => [
        'name' => [['locale' => null, 'scope' => null, 'data' => 'Classic tee']],
        'color' => [['locale' => null, 'scope' => null, 'data' => 'red']],
        'weight' => [['locale' => null, 'scope' => null, 'data' => ['amount' => '180', 'unit' => 'gram']]],
    ],
]));
```

A **family variant** says how a family's products vary — one or two levels of axes such as color, then size. A root **product model** holds the common values, sub-models the level-1 values, and **variant products** the last level, inheriting the rest. See [docs/04-products.md](docs/04-products.md).

## Ownership

Owners form chains of runtime-defined owner types — vendor → series → product, manufacturer → vendor → product, vendor → vendor → …, at any depth. Each owner type says which types may be its parent, whether it may be a root, and whether it owns products. Products and root product models belong to the deepest owner; variants take their root model's.

```php
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerTypeAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerTypeData;

app(CreateOwnerTypeAction::class)->execute(CreateOwnerTypeData::from(['code' => 'vendor']));
app(CreateOwnerTypeAction::class)->execute(CreateOwnerTypeData::from(['code' => 'series', 'parent_types' => ['vendor'], 'can_be_root' => false]));

app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => 'acme', 'type' => 'vendor']));
app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => 'classic', 'type' => 'series', 'parent' => 'acme']));
```

`GET /showroom/products?owner=acme` finds products owned by Acme or anything beneath it. See [docs/06-ownership.md](docs/06-ownership.md).

## Taxonomy

Categories form independent trees (a root category is a tree). Products and product models are filed in any number of categories from any trees; variants inherit their models' categories. Moving a category carries its branch.

```php
use RefactorCircus\Showroom\Domains\Category\Actions\CreateCategoryAction;
use RefactorCircus\Showroom\Domains\Category\Data\CreateCategoryData;

app(CreateCategoryAction::class)->execute(CreateCategoryData::from(['code' => 'master']));
app(CreateCategoryAction::class)->execute(CreateCategoryData::from(['code' => 'shirts', 'parent' => 'master']));
```

`GET /showroom/products?category=master` finds everything filed anywhere in that tree. See [docs/07-taxonomy.md](docs/07-taxonomy.md).

## Media

Assets live on any Laravel disk (S3-ready; nothing assumes a lasting local disk) and are added by upload, by a path already on the disk (presigned S3 uploads), or by URL. They link to products, product models and owners at any level of a chain, under roles such as `image` or `manual`; variants show their models' assets.

```http
POST /showroom/assets              {"url": "https://cdn.example.com/front.jpg"}
POST /showroom/assets/front/links  {"type": "product", "target": "TEE-001", "role": "image"}
```

See [docs/08-media.md](docs/08-media.md).

## Channels and locales

Locales are the languages content is written in; channels are where products are published, each with its locales, currencies and category tree. Localizable values must use an existing locale, scopable values an existing channel — and a value that is both, one of that channel's locales. Reads take `scope` and `locales[]` to return one channel's content.

```http
POST /showroom/locales   {"code": "en_US"}
POST /showroom/channels  {"code": "ecommerce", "locales": ["en_US"], "currencies": ["USD"]}
GET  /showroom/products/TEE-001?scope=ecommerce&locales[]=en_US
```

See [docs/09-channels.md](docs/09-channels.md).

## Associations, bundles and kits

Association types — cross-sell, accessories, compatible parts (two-way, mirrored automatically), bundles and kits (quantified) — relate products and product models:

```json
{
  "associations": {"cross_sell": {"products": ["CAP"], "product_models": []}},
  "quantified_associations": {"bundle": {"products": [{"identifier": "TEE", "quantity": 2}]}}
}
```

Each type sent replaces that type's targets; variants inherit their models' associations. See [docs/10-associations.md](docs/10-associations.md).

## Completeness, workflow and versioning

Families mark attributes required, on every channel or some; each product scores completeness per channel and locale. Products move `draft` → `in_review` → `approved`, and `publish` makes the current version the one storefronts read while the working copy stays editable. Every write is versioned with its author and changes, and can be reverted.

```http
POST /showroom/products/TEE-001/transitions   {"transition": "submit"}
GET  /showroom/products/TEE-001/versions/published
POST /showroom/products/TEE-001/revert         {"version": 3}
GET  /showroom/products?complete[scope]=ecommerce&complete[min]=100
```

See [docs/11-workflow.md](docs/11-workflow.md).

## Import, export and feeds

With [refactor-circus/impex](https://github.com/refactor-circus/impex) installed, bulk imports (CSV or JSONL, Akeneo columns), exports of any search, per-channel syndication feeds and ERP pushes run as Impex flows: resumable, retryable, schedulable and recorded in its ledger.

Switch on `showroom.impex.webhooks.enabled` and published products become an Impex stream: vendors subscribe to the categories, SKUs and topics (content, pricing, assets, resources, catalog) they want, and are pushed signed, batched changes — or pull them from a feed — instead of polling the API. Built for catalogues in the tens of millions. A feed can also leave through any Impex outbound channel (`deliver_through`), an SFTP drop for example.

For clients that still poll, `GET /showroom/products?updated_since=…` lists products where anything they show changed since then, inherited changes included, and `GET /showroom/products/{identifier}` answers `304` to a matching `If-None-Match`.

```http
POST /showroom/imports   file=@products.csv mode=upsert
POST /showroom/exports   {"family": "shirts", "format": "csv", "published": true}
```

See [docs/12-impex.md](docs/12-impex.md).

## Search

Products are searched through **Laravel Scout** when it is installed — Meilisearch, Typesense, Algolia, or a community driver such as one for Elasticsearch — and through the database otherwise, or through your own engine. Writes are synced to the index by queued jobs; `php artisan showroom:search:reindex` rebuilds it.

```php
// config/showroom.php — null picks Scout when installed, the database otherwise
'search' => ['engine' => 'scout'],
```

```http
GET /showroom/products?search=tee&filters[0][attribute]=color&filters[0][operator]=in&filters[0][value][]=red
```

See [docs/05-search.md](docs/05-search.md).

## HTTP API

Routes live under `/showroom` with the `api` middleware by default (`showroom.routes`). **Add authentication middleware before exposing them.**

| Method | Path | |
|---|---|---|
| `GET` / `POST` | `/showroom/attribute-groups` | List / create |
| `GET` / `PATCH` / `DELETE` | `/showroom/attribute-groups/{code}` | Show / update / delete |
| `GET` / `POST` | `/showroom/attributes` | List (`type`, `group`, `search`) / create |
| `GET` / `PATCH` / `DELETE` | `/showroom/attributes/{code}` | Show / update / delete |
| `GET` / `POST` | `/showroom/attributes/{code}/options` | List / create |
| `PATCH` / `DELETE` | `/showroom/attributes/{code}/options/{code}` | Update / delete |
| `GET` / `POST` | `/showroom/families` | List (`search`, `attribute`) / create |
| `GET` / `PATCH` / `DELETE` | `/showroom/families/{code}` | Show / update / delete |
| `GET` / `POST` | `/showroom/family-variants` | List / create |
| `GET` / `PATCH` / `DELETE` | `/showroom/family-variants/{code}` | Show / update / delete |
| `GET` / `POST` | `/showroom/product-models` | List / create |
| `GET` / `PATCH` / `DELETE` | `/showroom/product-models/{code}` | Show / update / delete |
| `GET` / `POST` | `/showroom/association-types` | List / create |
| `GET` / `PATCH` / `DELETE` | `/showroom/association-types/{code}` | Show / update / delete |
| `GET` / `POST` | `/showroom/locales` | List / create |
| `GET` / `PATCH` / `DELETE` | `/showroom/locales/{code}` | Show / update / delete |
| `GET` / `POST` | `/showroom/channels` | List / create |
| `GET` / `PATCH` / `DELETE` | `/showroom/channels/{code}` | Show / update / delete |
| `GET` / `POST` | `/showroom/assets` | List / create (`file`, `path` or `url`) |
| `GET` / `PATCH` / `DELETE` | `/showroom/assets/{code}` | Show / update or replace / delete |
| `POST` / `DELETE` | `/showroom/assets/{code}/links` | Link / unlink |
| `GET` / `POST` | `/showroom/categories` | List (`roots`, `parent`, `under`) / create |
| `GET` / `PATCH` / `DELETE` | `/showroom/categories/{code}` | Show / update or move / delete |
| `POST` | `/showroom/products/{identifier}/transitions` | Workflow transition |
| `GET` | `/showroom/products/{identifier}/versions` | History (and `/{n}`, `/latest`, `/published`) |
| `POST` | `/showroom/products/{identifier}/revert` | Restore a version |
| `GET` / `POST` | `/showroom/owner-types` | List / create |
| `GET` / `PATCH` / `DELETE` | `/showroom/owner-types/{code}` | Show / update / delete |
| `GET` / `POST` | `/showroom/owners` | List (`type`, `parent`, `under`, `roots`) / create |
| `GET` / `PATCH` / `DELETE` | `/showroom/owners/{code}` | Show / update or move / delete |
| `GET` / `POST` | `/showroom/products` | Search / create |
| `GET` / `PATCH` / `DELETE` | `/showroom/products/{identifier}` | Show / update / delete |
| `GET` | `/showroom/history` | Audit history, newest first (needs [refactor-circus/keen](https://github.com/Refactor-Circus/Keen) and `showroom.audit.enabled`; `404` without it) |

Product search is page-numbered; other listings are cursor paginated (`cursor`, `per_page`). A broken catalog rule — deleting a non-empty group, adding options to a text attribute — answers `409` with a message. See [docs/09-api.md](docs/09-api.md).

With `showroom.authorization` on (the default), every call - and every Atrium screen - requires an authenticated user and goes through the policies in `showroom.policies`. The bundled policies let any authenticated user manage the catalog; point a model at your own class to restrict it. Every model has one: associations, versions and completeness scores follow the policy of the product (or product model) they belong to, and the searchable product Scout indexes follows the product policy.

## MCP

The same operations as the HTTP API, as MCP tools calling the same Actions. Both transports ship disabled:

```php
// config/showroom.php
'mcp' => [
    'web' => ['enabled' => true, 'route' => 'mcp/showroom', 'middleware' => ['auth:api']],
    'local' => ['enabled' => true, 'handle' => 'showroom'],
],
```

The tools sit behind `search_tools` / `execute_tools`. The catalog is `ShowroomServer::TOOLS`. `list-showroom-history-tool` lists the audit history when refactor-circus/keen is installed. See [docs/10-mcp.md](docs/10-mcp.md).

## Cortex

When [`refactor-circus/cortex`](https://github.com/Refactor-Circus/cortex) is installed, the MCP server and every tool are registered with it, so Cortex agents can manage the catalog and the server instructions and tool descriptions can be overridden with published versions. Turn it off with `showroom.cortex.enabled`, or offer only some tools with `showroom.cortex.tools`.

## Dashboard

Showroom registers an Atrium plugin: a **Catalog** navigation group with product, product model, category, asset, owner, channel, family, attribute and attribute group pages, an import and export page, dashboard widgets (products by status, completeness, review queue, recent changes), a settings panel and global search. Atrium owns the dashboard's path, middleware and gate. Disable it with `showroom.ui.enabled`. See [docs/11-dashboard.md](docs/11-dashboard.md).

The screens follow Atrium's screen conventions: every action is an icon button (its label shows on hover), statuses are coloured dots (`info` only for products waiting in review and imports or exports waiting to run), and every navigation item has an icon.

Showroom ships no stylesheet or Blade components of its own: its screens are built from `x-atrium::*` components and the utilities Atrium safelists, and `tests/Feature/Ui/StylesTest.php` checks that with `RefactorCircus\Atrium\Testing\AtriumStyles`.

With [refactor-circus/keen](https://github.com/Refactor-Circus/Keen) installed, each product, product model, family, attribute, category, owner, asset and channel page shows that record's audit history, and the product list shows Showroom's (`<x-atrium::audit-trail source="showroom" />`); without it nothing renders. Products keep their own versions and revert alongside. Showroom teaches the audit log what to call its records: labelled records by the current locale's label (else the code), associations by their type and both ends, versions by what they version and their number.

With `showroom.authorization` on, the screens ask the policies exactly as the API does - the same ability on the same model or model class - so each navigation item, page, button, form and card appears only when its action would be allowed, and the action is refused otherwise. Views use the same check as `@showroomCan('update', $product) ... @endshowroomCan`. Forms a user may view but not save are shown read-only.

With [refactor-circus/pennantplus](https://github.com/Refactor-Circus/PennantPlus) installed, the `ShowroomSupportFeature` Pennant feature switches Showroom in Atrium on and off as a whole - navigation, widgets, settings, search and pages (which answer 404). It is on until its global value is set, and only the global value counts:

```php
use Laravel\Pennant\Feature;
use RefactorCircus\Showroom\Atrium\Features\ShowroomSupportFeature;

Feature::for(null)->deactivate(ShowroomSupportFeature::class);
```

Name a subclass or your own features in `showroom.atrium.features`, or empty it to never check one. Without refactor-circus/pennantplus nothing is checked.

## Code layout

Showroom is organised into domain modules under `src/Domains/{Domain}`, namespace `RefactorCircus\Showroom\Domains\{Domain}`. Each domain has its own service provider (registered by `RefactorCircus\Showroom\Domains\DomainServiceProvider`, which `ShowroomServiceProvider` registers), its API `routes.php`, and only the folders it uses: `Models/`, `Policies/`, `Resources/`, `Enums/`, `Data/`, `Actions/`, `Events/`, `Http/{Controllers,Requests}/`, `Mcp/{Tools,Requests}/`, `Concerns/`, `Services/`, `Support/`, `Contracts/`, `Exceptions/`, `Console/`.

| Domain | What lives there |
|--------|------------------|
| `Product` | products (`ProductModel`), statuses, product CRUD |
| `ProductModel` | product models (`ProductModelModel`) |
| `Attribute` | attributes, attribute groups and options, attribute types, value storage and validation |
| `Family` | families and family variants |
| `Owner` | owners and owner types |
| `Category` | category trees |
| `Channel` | channels and locales |
| `Association` | association types and associations |
| `Asset` | assets, their storage and links |
| `Workflow` | versions, completeness, transitions and revert |
| `Search` | search engines, the product index, `showroom:search:reindex` |
| `Transfer` | starting Impex imports and exports |

Eloquent models are named for their entity with a `Model` suffix, so a product is `RefactorCircus\Showroom\Domains\Product\Models\ProductModel` and a product model is `RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel`. Cross-domain code lives in `src/Support`; the Atrium screens, the Impex integration, the MCP server and the queued jobs keep their own top-level folders (`src/Atrium`, `src/Impex`, `src/Mcp`, `src/Jobs`).

Showroom stands on [refactor-circus/keystone](https://github.com/Refactor-Circus/Keystone), the runtime the Refactor Circus packages share. `ShowroomServiceProvider` extends its `PackageServiceProvider` and describes the package once (`Package::make('showroom', 'RefactorCircus\Showroom')`); requests, MCP tools and the server, the domain providers, the event contracts, `DispatchesModelEvents`, the authorizer and the Cortex integration are Keystone's. `ShowroomException` extends Keystone's `PackageException`. Impex flows, sources and actions and the queued jobs keep their class names because Impex runs and queued payloads store them.

## Commands

- `php artisan showroom:search:reindex` — recompute completeness and rebuild the product search index.

## Events

- **Model events:** every Eloquent hook of every model dispatches its own class, such as `RefactorCircus\Showroom\Domains\Attribute\Events\AttributeCreatedEvent`. Listen to `RefactorCircus\Keystone\Contracts\ModelLifecycleEvent` for all of them.
- **Action events:** every Action dispatches a starting and a finished event, such as `AttributeCreatingActionEvent` and `AttributeCreatedActionEvent`. Every Action writes inside a database transaction; finished events dispatch after it commits, and never when the Action throws or the transaction rolls back. The Impex flow steps (importing a row, exporting pages, joining an export, delivering a feed) fire the same pair. Listen to `ActionStartingEvent` or `ActionFinishedEvent` for a whole family.

## Roadmap

1. Catalog core — attributes ✅, families ✅, products, product models and variants ✅, search ✅, dynamic hierarchical ownership ✅
2. Taxonomy — category trees ✅
3. Media and assets ✅
4. Channels and locales ✅
5. Associations, bundles and kits ✅
6. Completeness and enrichment workflow ✅
7. Import / export via Impex ✅

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Showroom! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Refactor Circus](https://github.com/Refactor-Circus)
- [All Contributors](../../contributors)

## License

Showroom is open-sourced software licensed under the [MIT license](LICENSE.md).
