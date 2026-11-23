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

Every operation is one Action, reachable over a JSON API and an MCP server for agents. Showroom ships no UI of its own: a committed OpenAPI document and TypeScript client let any front end call the API with types, and [Atrium v2](#front-end-atrium-v2) is the admin panel built on them.

> **Status:** early development. The catalog core — attributes, families, products, product models and variants, pluggable search and hierarchical ownership — category trees, media, channels and locales, associations and bundles, completeness, workflow and versioning have landed; the rest of the [roadmap](#roadmap) follows.

## Installation

```bash
composer require refactor-circus/showroom

php artisan vendor:publish --tag="showroom-migrations"
php artisan migrate
```

Optionally publish the config or translations:

```bash
php artisan vendor:publish --tag="showroom-config"
php artisan vendor:publish --tag="showroom-lang"
```

`--tag="showroom"` publishes all of them at once. For an admin panel, see [Front end: Atrium v2](#front-end-atrium-v2).

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
| `GET` | `/showroom/stats` | Catalog figures: products per status and published, completeness per channel and locale, the review queue, the newest versions |
| `GET` | `/showroom/search` | Search every record type at once (`q`, `type`, `limit`) |
| `GET` | `/showroom/settings-summary` | How the API and MCP server are set up: authorization, routes, MCP transports, pagination |
| `GET` | `/showroom/history` | Audit history, newest first (needs [refactor-circus/keen](https://github.com/Refactor-Circus/Keen) and `showroom.audit.enabled`; `404` without it) |

Product search is page-numbered; other listings are cursor paginated (`cursor`, `per_page`). A broken catalog rule — deleting a non-empty group, adding options to a text attribute — answers `409` with a message. See [docs/09-api.md](docs/09-api.md).

`stats`, `search` and `settings-summary` are the read-only `Catalog` domain: views across the other domains for a front end's overview page, ⌘K search and settings panel. `stats` caches its completeness figures for `showroom.stats.cache_seconds` (60 by default; `0` reads them fresh). Search covers only the record types the caller may list (`viewAny`), and answers `403` when that is none of them; stats and the settings summary need `viewAny` on products.

With `showroom.authorization` on (the default), every call requires an authenticated user and goes through the policies in `showroom.policies`. The bundled policies let any authenticated user manage the catalog; point a model at your own class to restrict it. Every model has one: associations, versions and completeness scores follow the policy of the product (or product model) they belong to, and the searchable product Scout indexes follows the product policy.

## MCP

The same operations as the HTTP API, as MCP tools calling the same Actions. Both transports ship disabled:

```php
// config/showroom.php
'mcp' => [
    'web' => ['enabled' => true, 'route' => 'mcp/showroom', 'middleware' => ['auth:api']],
    'local' => ['enabled' => true, 'handle' => 'showroom'],
],
```

The tools sit behind `search_tools` / `execute_tools`. The catalog is `ShowroomServer::TOOLS`; `show-catalog-stats-tool` and `search-catalog-tool` answer with the same payloads as `GET /showroom/stats` and `GET /showroom/search`. `list-showroom-history-tool` lists the audit history when refactor-circus/keen is installed. See [docs/10-mcp.md](docs/10-mcp.md).

## Cortex

When [`refactor-circus/cortex`](https://github.com/Refactor-Circus/cortex) is installed, the MCP server and every tool are registered with it, so Cortex agents can manage the catalog and the server instructions and tool descriptions can be overridden with published versions. Turn it off with `showroom.cortex.enabled`, or offer only some tools with `showroom.cortex.tools`.

## OpenAPI and TypeScript client

The API is described by `openapi.json` at the package root, exported with [Scramble](https://scramble.dedoc.co) from the Testbench workbench (Scramble is a dev dependency only; hosts install nothing). Every route Showroom registers by default is in it, with its method, path, parameters and request body, and the `stats`, `search` and `settings-summary` responses carry their full schemas.

`client/` is a typed fetch client, `@refactor-circus/showroom-client`, built on [openapi-fetch](https://openapi-ts.dev/openapi-fetch/) with types generated by openapi-typescript from `openapi.json`. Its built `dist/` is committed, so it installs without a build step. It is not published to a registry: install it as a git dependency at a release tag. npm cannot install a subfolder of a repository, so use pnpm or Yarn:

```bash
pnpm add "github:Refactor-Circus/Showroom#v2.0.0&path:client"
```

```ts
import { createShowroomClient } from '@refactor-circus/showroom-client';

const showroom = createShowroomClient({ baseUrl: 'https://app.test/showroom', credentials: 'include' });

const { data } = await showroom.GET('/search', { params: { query: { q: 'tee', type: 'products' } } });
```

`createShowroomClient({ baseUrl, headers?, fetch?, credentials? })` returns openapi-fetch's typed `Client<paths>`; `paths`, `components` and `operations` are exported too. See [client/README.md](client/README.md).

After changing a route, a request's rules, a Data's `rules()` or a resource, regenerate both and commit them:

```bash
composer client:generate
```

It runs `composer openapi:export` (which writes `openapi.json`), then `npm --prefix client ci` and `npm --prefix client run generate` (openapi-typescript, a sha256 stamp of `openapi.json` on the first line of `client/src/schema.d.ts`, and `tsc`). `tests/OpenApi/OpenApiTest.php` fails while either is stale, without needing Node: it exports the document again and compares it with the committed one, and checks the client's stamp. A CI job with Node runs `composer client:generate` and fails on any diff.

## Front end: Atrium v2

Showroom has no screens. [Atrium](https://github.com/Refactor-Circus/Atrium) v2 is the optional admin panel for it: install it in the host (`composer require refactor-circus/atrium`) and its Nuxt layer brings the catalog pages, the overview widgets, ⌘K search and the settings panel, calling Showroom's API through `@refactor-circus/showroom-client`. Atrium owns the panel's path, middleware, navigation and links, and builds them from each record's `type` and `id`; see Atrium's Showroom integration for setup. Showroom needs no configuration for it, and the Actions, JSON API and MCP server work the same without it.

Showroom keeps no transfer record and has no transfers-list endpoint: an import or export run is Impex's. Atrium lists Showroom's runs through Impex's runs API (`GET {impex prefix}/runs`, filtered by `flow`), and starting a run stays on `POST /showroom/imports` and `POST /showroom/exports`. The export form's choices (families, channels, root categories) come from the existing list endpoints.

History comes from [refactor-circus/keen](https://github.com/Refactor-Circus/Keen) (`GET /showroom/history`). Showroom teaches the audit log what to call its records: labelled records by the current locale's label (else the code), associations by their type and both ends, versions by what they version and their number.

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
| `Catalog` | read-only views across the others: stats, search, settings summary |

Eloquent models are named for their entity with a `Model` suffix, so a product is `RefactorCircus\Showroom\Domains\Product\Models\ProductModel` and a product model is `RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel`. Cross-domain code lives in `src/Support`; the Impex integration, the MCP server and the queued jobs keep their own top-level folders (`src/Impex`, `src/Mcp`, `src/Jobs`).

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
