# Release Notes

## [Unreleased](https://github.com/Refactor-Circus/Showroom/commits/main)

### Breaking

- Actions take their input as Data objects, not arrays. Each Action that took an array (`$data`, `$filters`, `$options`) now takes a `final readonly` `{Action}Data` from its domain's `Data` namespace (`RefactorCircus\Showroom\Domains\{Domain}\Data`), built on Keystone's `RefactorCircus\Keystone\Data\Data`: `CreateAttributeData`, `UpdateProductData`, `ListProductsData` and so on. Build one with `XData::from([...])`, which validates against the Data's `rules()` and throws a `ValidationException`, or with `new XData(...)` for values your code vouches for. Update Data leave out what they do not change (`Missing`). Product, product model and attribute values, associations and labels stay arrays keyed by code inside the Data. The `rules()` static methods moved from the Actions to their Data classes, and the starting action events that carried the input array (`ProductCreatingActionEvent::$data`, `ProductsListingActionEvent::$filters`, ...) now carry the Data, as do the finished events that carried input (`AssetAttachedActionEvent`, `AssetDetachedActionEvent`, `ProductRevertedActionEvent`, `ProductTransitionedActionEvent`). The Impex flows and steps in `RefactorCircus\Showroom\Impex` keep their names and array payloads.
- Lists of structured items inside the Data are lists of item Data (Keystone's `#[ListOf]`), with the same input shape and rules: `CreateFamilyData::$attributes` / `UpdateFamilyData::$attributes` hold `FamilyAttributeData` (`attribute`, `isRequired`, `sortOrder`, `requiredChannels`), `CreateFamilyVariantData::$levels` / `UpdateFamilyVariantData::$levels` hold `FamilyLevelData` (`axes`, `attributes`), and `ListProductsData::$filters` / `StartExportData::$filters` hold `Search\Data\Filter`; their `complete` is a `Search\Data\Complete`. Code building these Data with `new` passes the item Data. `Filter`, `Complete` and `Attribute\Data\ValueFilter` are Keystone Data with their own `rules()`, and their `fromArray()` is removed (use `from()`, `fromValidated()` or `new`), as is `ProductQuery::fromArray()`: `ListProductsData::query()` builds the engine's `ProductQuery`. Export runs store the same query as before, so stored runs replay.
- MCP list tools answer with their page under `meta` (`meta.next_cursor`, or `meta.current_page`, `meta.last_page` and `meta.total` for `list-products-tool`) instead of top-level `next_cursor`, `page`, `last_page` and `total` keys, and take `per_page` up to `showroom.pagination.max_per_page`. `start-import-tool` and `start-export-tool` answer with the run under `data`, like every other tool.
- Guests calling the JSON API while `showroom.authorization` is on get 401 instead of 403; signed-in users without the ability still get 403.

- Renamed Keystone to Showroom and moved to the Refactor Circus organisation: the package is now `refactor-circus/showroom` with the PHP namespace `RefactorCircus\Showroom` (it was `jayi/keystone` and `JayI\Keystone`). Everything named after the package follows: `ShowroomServiceProvider`, the `Showroom` facade, `config/showroom.php` and its `showroom.*` keys, `showroom.*` route names, the `showroom::` view and translation namespaces, `@showroomCan`, the `showroom` audit source, MCP server and tool names, and the `showroom_*` tables and morph aliases (`showroom_product`, `showroom_owner`, `showroom_product_model`). Existing databases need their `keystone_*` tables renamed and stored `keystone_*` morph types updated; republish the config as `config/showroom.php`. Update `composer.json` requirements and `use` statements. Old class names are not kept as aliases, so stored values written under them - polymorphic `*_type` columns, audit subjects, Pennant feature names - need updating to the new names.

### Changed

- Product lists (`ListProductsAction`: `GET /showroom/products`, `list-products-tool`, the dashboard) load the owner and categories of each product and the models above it, and product model lists (`ListProductModelsAction`) those of each model and its root, so a page costs the same number of queries however many records it holds. `ListProductsAction::eagerLoads()` names the set.
- Exports load each page's owners, categories, associations and (with `published`) published versions in a fixed number of queries (`ExportProductPages::eagerLoads()`, `Versions::publishedSnapshots()`, `ExportRecord::fromSnapshot()`). On the database engine they walk the products by identifier and id instead of by offset, never count them, and keep the last key in the resume cursor (`{"page", "count", "after"}`); runs that yielded with the older `{"page", "count"}` cursor resume from their page. `DatabaseEngine::keyset()` does the walk.
- A channel update queues product syncs only when its code, locales, currencies or category tree change; a label change alone no longer touches products (or their `changed_at`). Creating or changing a channel, a family's attributes, or moving an owner or category no longer lists the affected products inside the request's transaction: one queued `Jobs\QueueProductSyncs` job (after commit, on the `showroom.search.queue` connection and queue) lists them in chunks and queues their syncs. Deleting a category lists its products in chunks rather than all at once.
- Completeness is refreshed per batch: each family's required attributes are read once, and a batch's scores are replaced with one delete and chunked inserts inside a transaction.
- Search documents for Scout are built per batch (`ProductDocument::many()`): attribute types, and the owner and category chains, are read once for the batch instead of once per product.
- `PurgeAttributeValues` and `PurgeValueSlots` queue product syncs chunk by chunk instead of collecting every affected id first.
- An update to an approved product takes its version snapshot once instead of twice (`Versions::hasChanges()` and `Versions::record()` take an optional `$snapshot`).
- The dashboard's product status widget counts in one grouped query, and the completeness widget caches its figures for `showroom.atrium.widget_cache_seconds` (new, default 60; 0 turns caching off).
- New migration `2026_10_12_000001_add_showroom_performance_indexes`: indexes on the foreign keys PostgreSQL and SQLite leave unindexed (`products.owner_id`, `product_models.owner_id`, `product_models.family_variant_id`, `owners.owner_type_id`, `product_completeness.locale_id`, `product_unique_values.product_id`), on products' `created_at`, `updated_at` and `published_version`, and on the `path` of owners and categories in the form each driver's prefix `LIKE` can use (see the installation docs). Publish the migrations again to pick it up.

- The per-row import and per-page export step events (`ProductRowImporting`/`ProductRowImported`, `ProductPagesExporting`/`ProductPagesExported`) implement refactor-circus/keystone's `UnauditedActionEvent`, so Keen writes no audit entry per row or page; the product Actions a row runs, the export join and the feed delivery are still recorded.
- Every Action writes inside one database transaction, so a failure leaves nothing half-written and fires no finished event. Asset files follow the transaction: a stored upload is removed if the asset is rolled back (also by a surrounding transaction, such as the dashboard's upload-and-link), and the file a replacement or delete drops is removed only once the change commits. `JoinProductExport` removes an export's part files only once its asset is recorded.
- The Impex flow steps in `RefactorCircus\Showroom\Impex\Actions` dispatch a starting and a finished event like every Action: `ProductRowImportingActionEvent` / `ProductRowImportedActionEvent` (`ImportProductRow`), `ProductPagesExportingActionEvent` / `ProductPagesExportedActionEvent` (`ExportProductPages`, once per resume), `ProductExportJoiningActionEvent` / `ProductExportJoinedActionEvent` (`JoinProductExport`) and `FeedDeliveringActionEvent` / `FeedDeliveredActionEvent` (`DeliverFeed`), in `RefactorCircus\Showroom\Domains\Transfer\Events`.
- `Search\Models\SearchableProductModel`, the Scout stand-in, fires its lifecycle events like every other model (`SearchableProduct{Hook}Event` in `RefactorCircus\Showroom\Domains\Search\Events`).
- Every MCP tool declares an output schema, and every API resource describes its fields with `schema()`. Tools answer through the resources' `toStructuredResponse()`, so the MCP payload matches the HTTP one.
- `start-import-tool` and `start-export-tool` answer with Impex's `RunResource::toStructuredResponse()` and declare its `structuredSchema()`, so their output schema now lists the run's `status`, `trigger` and `close_policy` values; Showroom's hand-written `Domains\Transfer\Support\RunSchema` is removed.
- Missing records on Showroom's API routes answer `{"message": "Not found."}` rather than naming the model class.
- Showroom audits only while refactor-circus/keen is installed and the new `showroom.audit.enabled` config key is on (the default). Otherwise Keen records nothing of Showroom's, the history route answers 404 and `list-showroom-history-tool` is not offered; its audit-log labels are registered only while Showroom audits.

### Added

- `ProductIndex::deferring(callable)`: holds back the syncs queued while the callback runs and queues them together, one job per 500 products, for code that writes many products in one process. `ProductIndex::queueWithin(SyncScope, ?id)` and `productsWithin(SyncScope, ?id)` queue or list a scope's products (`Search\Enums\SyncScope`: `all`, `family`, `owner`, `category`).

- `showroom.tables`: every table Showroom creates can be renamed in the config before migrating (`RefactorCircus\Showroom\Support\Tables`). Migrations, models, validation rules, the database search engine, the dashboard's completeness widget and the webhook queries all read it; a published config that lists only some tables keeps the defaults for the rest. `AssetModel::LINKS` is removed - use `Tables::name('asset_links')`. The Scout index falls back to the products table's name when `showroom.search.scout.index` is unset.
- Every model has a policy in `showroom.policies`: `AssociationPolicy`, `VersionPolicy` and `CompletenessPolicy` follow the policy of the product or product model the record belongs to (`view` to read, `update` to change), and `SearchableProductPolicy` answers as the product policy does for the same row. `CompletenessModel` gains a `product()` relation.
- **Product webhooks** (`showroom.impex.webhooks`, off by default): published products as an Impex stream (`showroom.products`) that vendors subscribe to by category, owner, family, model or SKU, and by topic — `content`, `pricing`, `assets`, `resources`, `catalog` — in `thin`, `slice` or `full` format, narrowed to a channel and locales. Changes are pushed signed and batched, or pulled from a feed; unpublished, deleted and out-of-scope products reach vendors as removed; full exports start a new vendor off. Product changes are reported to Impex from `ProductIndex::queue()` (which now fires `RefactorCircus\Showroom\Domains\Search\Events\ProductsQueuedForSync`), deletions and asset links.
- Feeds can deliver through any Impex outbound channel with `deliver_through`, taking the channel's transport, signing and headers.
- `updated_since` on `GET /showroom/products`, `list-products-tool` and `ListProductsAction`, on every search engine. It filters on a new `changed_at` column (in the product resource too), set by `SyncProductIndex` whenever anything a product shows changes — inherited changes, refilings, associations and assets included — where `updated_at` moves only with the product's own row.
- `GET /showroom/products/{identifier}` sends an `ETag` and answers `304` to a matching `If-None-Match`.
- The package's section in Atrium's sidebar rail has its own icon (`cube`) and a fixed place in the rail.
- An **Audit log** link in the package's sidebar group, opening its own audit log in Atrium (`/atrium/history/showroom`), shown while an audit log (refactor-circus/keen) is installed and to those who may read the package's history.

### Breaking

- The Elasticsearch engine and its `refactor-circus/stretch` dependency are removed, with the `showroom.search.elasticsearch` config. `showroom.search.engine` now defaults to `null`: Scout when `laravel/scout` is installed, the database otherwise. An application still naming `elasticsearch` gets an error pointing at a Scout driver or its own `SearchEngine`. Facets remain in the search contract for custom engines; the bundled engines return none.
- Showroom ships no stylesheet: `resources/css/atrium.css` and its registration with Atrium's style hook are removed. The screens use only Atrium's components and safelisted utilities (bare form controls in table cells, `description-list`, `progress`, `flash`), and `ui/partials/status.blade.php` is replaced by `<x-atrium::flash />`, which also shows the first validation error. Requires a refactor-circus/atrium with those components. Published views that include `showroom::ui.partials.status` must switch to `<x-atrium::flash />`.
- `RefactorCircus\Showroom\Atrium\Http\Controllers\Concerns\AuthorizesScreens` is removed; the screen controllers use Atrium's `RefactorCircus\Atrium\Http\Controllers\Concerns\AuthorizesScreens`. `ScreenAccess::allows()` now delegates to `RefactorCircus\Atrium\Support\ScreenAccess`, and `ShowroomPlugin` uses the base plugin's `featuresFromConfig()`, `key()` and `label()`.

- Showroom now stands on [refactor-circus/keystone](https://github.com/Refactor-Circus/Keystone), the runtime the Refactor Circus packages share, and its local copies are removed in favour of Keystone's classes:
  - `RefactorCircus\Showroom\Contracts\{ActionStartingEvent,ActionFinishedEvent,ModelLifecycleEvent}` → `RefactorCircus\Keystone\Contracts\*`. Listening to a Keystone contract now hears every package of the suite.
  - `RefactorCircus\Showroom\Support\Models\Concerns\DispatchesModelEvents` → `RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents`. A subclass of a Showroom model now fires the Showroom model's events.
  - `RefactorCircus\Showroom\Support\ServiceProvider` → `RefactorCircus\Keystone\Support\ServiceProvider`; `RefactorCircus\Showroom\Support\Authorizer` → `RefactorCircus\Keystone\Auth\Authorizer::for($package)`.
  - `RefactorCircus\Showroom\Http\Request` → `RefactorCircus\Keystone\Http\Requests\Request`; `RefactorCircus\Showroom\Mcp\Request` → `RefactorCircus\Keystone\Mcp\Requests\Request`, whose calls run inside the `mcp` surface; `RefactorCircus\Showroom\Mcp\Tool` → `RefactorCircus\Keystone\Mcp\Tool`.
  - `RefactorCircus\Showroom\Cortex\CortexIntegration` → `RefactorCircus\Keystone\Cortex\CortexIntegration::for($package)`, which also marks agent tool calls with the `cortex` surface.
  - `ShowroomServiceProvider` extends `PackageServiceProvider`, and `ShowroomServer` extends `RefactorCircus\Keystone\Mcp\Server`. `ShowroomException` is now an abstract `RefactorCircus\Keystone\Exceptions\PackageException` that still answers 409, and the bundled policy base extends Keystone's `Policy`. Config keys, route names and tool names are unchanged.
- The source is reorganised into domain modules under `src/Domains/{Domain}` (`RefactorCircus\Showroom\Domains\{Domain}`), each with its own service provider registered by `RefactorCircus\Showroom\Domains\DomainServiceProvider`: **Product**, **ProductModel**, **Attribute** (attributes, groups, options, values), **Family** (families and family variants), **Owner** (owners and owner types), **Category**, **Channel** (channels and locales), **Association**, **Asset**, **Workflow** (versions, completeness, transitions, revert), **Search** and **Transfer** (starting Impex imports and exports). Cross-domain code moved to `RefactorCircus\Showroom\Support`, and the Atrium screens to `RefactorCircus\Showroom\Atrium`. `ShowroomServiceProvider` keeps its name. Config keys, route names, MCP tool names, views, translations, publish tags and `@showroomCan` are unchanged. There are no aliases for the old class names.
- The Eloquent models are renamed to end in `Model`. Their old class names are kept as morph aliases, so any `*_type` value written under them still resolves; products, product models and owners keep writing their `showroom_product`, `showroom_product_model` and `showroom_owner` aliases, and the other models keep writing their old class names. Point published `showroom.policies` keys at the new names:
  - `RefactorCircus\Showroom\Models\Asset` → `RefactorCircus\Showroom\Domains\Asset\Models\AssetModel`
  - `RefactorCircus\Showroom\Models\Association` → `RefactorCircus\Showroom\Domains\Association\Models\AssociationModel`
  - `RefactorCircus\Showroom\Models\AssociationType` → `RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel`
  - `RefactorCircus\Showroom\Models\Attribute` → `RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel`
  - `RefactorCircus\Showroom\Models\AttributeGroup` → `RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel`
  - `RefactorCircus\Showroom\Models\AttributeOption` → `RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel`
  - `RefactorCircus\Showroom\Models\Category` → `RefactorCircus\Showroom\Domains\Category\Models\CategoryModel`
  - `RefactorCircus\Showroom\Models\Channel` → `RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel`
  - `RefactorCircus\Showroom\Models\Completeness` → `RefactorCircus\Showroom\Domains\Workflow\Models\CompletenessModel`
  - `RefactorCircus\Showroom\Models\Family` → `RefactorCircus\Showroom\Domains\Family\Models\FamilyModel`
  - `RefactorCircus\Showroom\Models\FamilyVariant` → `RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel`
  - `RefactorCircus\Showroom\Models\Locale` → `RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel`
  - `RefactorCircus\Showroom\Models\Owner` → `RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel`
  - `RefactorCircus\Showroom\Models\OwnerType` → `RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel`
  - `RefactorCircus\Showroom\Models\Product` → `RefactorCircus\Showroom\Domains\Product\Models\ProductModel`
  - `RefactorCircus\Showroom\Models\ProductModel` → `RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel`
  - `RefactorCircus\Showroom\Models\Version` → `RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel`
- Relations now name their foreign and pivot keys, which Eloquent used to guess from the old class names; factories' `for()` needs the relationship name where it guessed it from a class (`AttributeOptionModel::factory()->for($attribute, 'attribute')`).
- `ShowroomSupportFeature` moved to `RefactorCircus\Showroom\Atrium\Features` and keeps its stored Pennant name, `RefactorCircus\Showroom\Features\ShowroomSupportFeature`, through Pennant's `#[Name]` attribute.
- `routes/showroom.php` is replaced by a `routes.php` per domain, loaded inside the same `showroom.` group; the routes, their names and middleware are unchanged.
- Model events (formerly `Events\Model`) and action events (formerly `Events\Action`) now live in their domain's `Events` namespace, keeping their class names. Model events are found from the model's domain and its name less the `Model` suffix.
- Unchanged, because Impex runs and queued payloads store their class names: `RefactorCircus\Showroom\Impex\*` (flows, sources, actions) and `RefactorCircus\Showroom\Jobs\*`.
- Actions, HTTP requests, MCP tools and MCP requests keep their class names and move to their domain:
  - `RefactorCircus\Showroom\Domains\Asset\Actions`: every Action for the domain (7 classes)
  - `RefactorCircus\Showroom\Domains\Association\Actions`: every Action for the domain (5 classes)
  - `RefactorCircus\Showroom\Domains\Attribute\Actions`: every Action for the domain (14 classes)
  - `RefactorCircus\Showroom\Domains\Category\Actions`: every Action for the domain (5 classes)
  - `RefactorCircus\Showroom\Domains\Channel\Actions`: every Action for the domain (10 classes)
  - `RefactorCircus\Showroom\Domains\Family\Actions`: every Action for the domain (10 classes)
  - `RefactorCircus\Showroom\Domains\Owner\Actions`: every Action for the domain (10 classes)
  - `RefactorCircus\Showroom\Domains\Product\Actions`: every Action for the domain (5 classes)
  - `RefactorCircus\Showroom\Domains\ProductModel\Actions`: every Action for the domain (5 classes)
  - `RefactorCircus\Showroom\Domains\Workflow\Actions`: every Action for the domain (4 classes)
  - `RefactorCircus\Showroom\Domains\Transfer\Actions`: every Action for the domain (2 classes)
  - `RefactorCircus\Showroom\Domains\Asset\Http\Requests`: every HTTP request for the domain (8 classes)
  - `RefactorCircus\Showroom\Domains\Association\Http\Requests`: every HTTP request for the domain (6 classes)
  - `RefactorCircus\Showroom\Domains\Attribute\Http\Requests`: every HTTP request for the domain (17 classes)
  - `RefactorCircus\Showroom\Domains\Category\Http\Requests`: every HTTP request for the domain (6 classes)
  - `RefactorCircus\Showroom\Domains\Channel\Http\Requests`: every HTTP request for the domain (12 classes)
  - `RefactorCircus\Showroom\Domains\Family\Http\Requests`: every HTTP request for the domain (12 classes)
  - `RefactorCircus\Showroom\Domains\Owner\Http\Requests`: every HTTP request for the domain (12 classes)
  - `RefactorCircus\Showroom\Domains\ProductModel\Http\Requests`: every HTTP request for the domain (6 classes)
  - `RefactorCircus\Showroom\Domains\Product\Http\Requests`: every HTTP request for the domain (6 classes)
  - `RefactorCircus\Showroom\Domains\Workflow\Http\Requests`: every HTTP request for the domain (4 classes)
  - `RefactorCircus\Showroom\Domains\Transfer\Http\Requests`: every HTTP request for the domain (2 classes)
  - `RefactorCircus\Showroom\Domains\Asset\Mcp\Requests`: every MCP request for the domain (8 classes)
  - `RefactorCircus\Showroom\Domains\Association\Mcp\Requests`: every MCP request for the domain (6 classes)
  - `RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests`: every MCP request for the domain (17 classes)
  - `RefactorCircus\Showroom\Domains\Category\Mcp\Requests`: every MCP request for the domain (6 classes)
  - `RefactorCircus\Showroom\Domains\Channel\Mcp\Requests`: every MCP request for the domain (12 classes)
  - `RefactorCircus\Showroom\Domains\Family\Mcp\Requests`: every MCP request for the domain (12 classes)
  - `RefactorCircus\Showroom\Domains\Owner\Mcp\Requests`: every MCP request for the domain (12 classes)
  - `RefactorCircus\Showroom\Domains\Product\Mcp\Requests`: every MCP request for the domain (6 classes)
  - `RefactorCircus\Showroom\Domains\ProductModel\Mcp\Requests`: every MCP request for the domain (6 classes)
  - `RefactorCircus\Showroom\Domains\Workflow\Mcp\Requests`: every MCP request for the domain (4 classes)
  - `RefactorCircus\Showroom\Domains\Transfer\Mcp\Requests`: every MCP request for the domain (2 classes)
  - `RefactorCircus\Showroom\Domains\Asset\Mcp\Tools`: every MCP tool for the domain (7 classes)
  - `RefactorCircus\Showroom\Domains\Association\Mcp\Tools`: every MCP tool for the domain (5 classes)
  - `RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools`: every MCP tool for the domain (14 classes)
  - `RefactorCircus\Showroom\Domains\Category\Mcp\Tools`: every MCP tool for the domain (5 classes)
  - `RefactorCircus\Showroom\Domains\Channel\Mcp\Tools`: every MCP tool for the domain (10 classes)
  - `RefactorCircus\Showroom\Domains\Family\Mcp\Tools`: every MCP tool for the domain (10 classes)
  - `RefactorCircus\Showroom\Domains\Owner\Mcp\Tools`: every MCP tool for the domain (10 classes)
  - `RefactorCircus\Showroom\Domains\ProductModel\Mcp\Tools`: every MCP tool for the domain (5 classes)
  - `RefactorCircus\Showroom\Domains\Product\Mcp\Tools`: every MCP tool for the domain (5 classes)
  - `RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools`: every MCP tool for the domain (4 classes)
  - `RefactorCircus\Showroom\Domains\Transfer\Mcp\Tools`: every MCP tool for the domain (2 classes)
  - `RefactorCircus\Showroom\Domains\Asset\Events`: every model and action event for the domain (24 classes)
  - `RefactorCircus\Showroom\Domains\Association\Events`: every model and action event for the domain (30 classes)
  - `RefactorCircus\Showroom\Domains\Attribute\Events`: every model and action event for the domain (58 classes)
  - `RefactorCircus\Showroom\Domains\Category\Events`: every model and action event for the domain (20 classes)
  - `RefactorCircus\Showroom\Domains\Channel\Events`: every model and action event for the domain (40 classes)
  - `RefactorCircus\Showroom\Domains\Transfer\Events`: every model and action event for the domain (4 classes)
  - `RefactorCircus\Showroom\Domains\Family\Events`: every model and action event for the domain (40 classes)
  - `RefactorCircus\Showroom\Domains\Owner\Events`: every model and action event for the domain (40 classes)
  - `RefactorCircus\Showroom\Domains\Product\Events`: every model and action event for the domain (20 classes)
  - `RefactorCircus\Showroom\Domains\ProductModel\Events`: every model and action event for the domain (20 classes)
  - `RefactorCircus\Showroom\Domains\Workflow\Events`: every model and action event for the domain (28 classes)
- Every other moved class, by its new namespace (old names relative to `RefactorCircus\Showroom`):
  - `RefactorCircus\Showroom\Support`: `Access\Authorizer`
  - `RefactorCircus\Showroom\Domains\Category\Concerns`: `Actions\Concerns\AssignsCategories`
  - `RefactorCircus\Showroom\Domains\Owner\Concerns`: `Actions\Concerns\AssignsOwners`, `Actions\Concerns\PlacesOwners`, `Actions\Concerns\WritesOwnerTypes`
  - `RefactorCircus\Showroom\Support\Concerns`: `Actions\Concerns\MovesInTree`
  - `RefactorCircus\Showroom\Domains\Asset\Concerns`: `Actions\Concerns\StoresAssetFiles`, `Models\Concerns\HasAssets`
  - `RefactorCircus\Showroom\Domains\Channel\Concerns`: `Actions\Concerns\WritesChannels`
  - `RefactorCircus\Showroom\Domains\Family\Concerns`: `Actions\Concerns\WritesFamilyAttributes`, `Actions\Concerns\WritesFamilyVariantLevels`
  - `RefactorCircus\Showroom\Domains\Product\Concerns`: `Actions\Concerns\WritesProducts`
  - `RefactorCircus\Showroom\Domains\Attribute\Concerns`: `Actions\Concerns\WritesValues`, `Models\Concerns\HasValues`
  - `RefactorCircus\Showroom\Domains\Association\Services`: `Associations\Associations`
  - `RefactorCircus\Showroom\Domains\Search\Console\Commands`: `Console\Commands\ReindexProductsCommand`
  - `RefactorCircus\Showroom\Domains\Search\Contracts`: `Contracts\SearchEngine`
  - `RefactorCircus\Showroom\Domains\Attribute\Enums`: `Enums\AttributeType`
  - `RefactorCircus\Showroom\Domains\Product\Enums`: `Enums\ProductStatus`
  - `RefactorCircus\Showroom\Domains\Workflow\Enums`: `Enums\Transition`
  - `RefactorCircus\Showroom\Domains\Attribute\Exceptions`: `Exceptions\AttributeGroupNotEmptyException`, `Exceptions\AttributeHasNoOptionsException`, `Exceptions\AttributeLabelsFamiliesException`
  - `RefactorCircus\Showroom\Domains\Transfer\Exceptions`: `Exceptions\ImpexMissingException`
  - `RefactorCircus\Showroom\Domains\Workflow\Exceptions`: `Exceptions\InvalidTransitionException`
  - `RefactorCircus\Showroom\Domains\Search\Exceptions`: `Exceptions\UnsupportedSearchException`
  - `RefactorCircus\Showroom\Atrium\Features`: `Features\ShowroomSupportFeature`
  - `RefactorCircus\Showroom\Domains\Asset\Http\Controllers`: `Http\Controllers\AssetController`
  - `RefactorCircus\Showroom\Domains\Association\Http\Controllers`: `Http\Controllers\AssociationTypeController`
  - `RefactorCircus\Showroom\Domains\Attribute\Http\Controllers`: `Http\Controllers\AttributeController`, `Http\Controllers\AttributeGroupController`, `Http\Controllers\AttributeOptionController`
  - `RefactorCircus\Showroom\Domains\Category\Http\Controllers`: `Http\Controllers\CategoryController`
  - `RefactorCircus\Showroom\Domains\Channel\Http\Controllers`: `Http\Controllers\ChannelController`, `Http\Controllers\LocaleController`
  - `RefactorCircus\Showroom\Domains\Family\Http\Controllers`: `Http\Controllers\FamilyController`, `Http\Controllers\FamilyVariantController`
  - `RefactorCircus\Showroom\Domains\Transfer\Http\Controllers`: `Http\Controllers\ImpexController`
  - `RefactorCircus\Showroom\Domains\Owner\Http\Controllers`: `Http\Controllers\OwnerController`, `Http\Controllers\OwnerTypeController`
  - `RefactorCircus\Showroom\Domains\Product\Http\Controllers`: `Http\Controllers\ProductController`
  - `RefactorCircus\Showroom\Domains\ProductModel\Http\Controllers`: `Http\Controllers\ProductModelController`
  - `RefactorCircus\Showroom\Domains\Workflow\Http\Controllers`: `Http\Controllers\ProductWorkflowController`
  - `RefactorCircus\Showroom\Domains\Asset\Resources`: `Http\Resources\AssetResource`, `Http\Resources\LinkedAssets`
  - `RefactorCircus\Showroom\Domains\Association\Resources`: `Http\Resources\AssociationTypeResource`
  - `RefactorCircus\Showroom\Domains\Attribute\Resources`: `Http\Resources\AttributeGroupResource`, `Http\Resources\AttributeOptionResource`, `Http\Resources\AttributeResource`
  - `RefactorCircus\Showroom\Domains\Category\Resources`: `Http\Resources\CategoryResource`
  - `RefactorCircus\Showroom\Domains\Channel\Resources`: `Http\Resources\ChannelResource`, `Http\Resources\LocaleResource`
  - `RefactorCircus\Showroom\Domains\Family\Resources`: `Http\Resources\FamilyAttributeResource`, `Http\Resources\FamilyResource`, `Http\Resources\FamilyVariantResource`
  - `RefactorCircus\Showroom\Domains\Owner\Resources`: `Http\Resources\OwnerResource`, `Http\Resources\OwnerTypeResource`
  - `RefactorCircus\Showroom\Domains\ProductModel\Resources`: `Http\Resources\ProductModelResource`
  - `RefactorCircus\Showroom\Domains\Product\Resources`: `Http\Resources\ProductResource`
  - `RefactorCircus\Showroom\Domains\Workflow\Resources`: `Http\Resources\VersionResource`, `Http\Resources\VersionSummaryResource`
  - `RefactorCircus\Showroom\Atrium\Http\Controllers`: `Http\Ui\AssetUiController`, `Http\Ui\AssociationUiController`, `Http\Ui\AttributeGroupUiController`, `Http\Ui\AttributeUiController`, `Http\Ui\CategoryUiController`, `Http\Ui\ChannelUiController`, `Http\Ui\FamilyUiController`, `Http\Ui\FamilyVariantUiController`, `Http\Ui\OwnerTypeUiController`, `Http\Ui\OwnerUiController`, `Http\Ui\ProductModelUiController`, `Http\Ui\ProductUiController`, `Http\Ui\TransferUiController`
  - `RefactorCircus\Showroom\Atrium\Support`: `Http\Ui\CategoryCodes`, `Http\Ui\EditingSlot`, `Http\Ui\Labels`, `Http\Ui\ValueForm`
  - `RefactorCircus\Showroom\Atrium\Http\Controllers\Concerns`: `Http\Ui\Concerns\AuthorizesScreens`
  - `RefactorCircus\Showroom\Atrium`: `Http\Ui\ScreenAccess`
  - `RefactorCircus\Showroom\Domains\Asset\Services`: `Media\AssetLinks`, `Media\AssetStorage`
  - `RefactorCircus\Showroom\Domains\Asset\Data`: `Media\StoredFile`
  - `RefactorCircus\Showroom\Support\Models\Concerns`: `Models\Concerns\DispatchesModelEvents`, `Models\Concerns\HasLabels`, `Models\Concerns\HasPath`
  - `RefactorCircus\Showroom\Domains\Association\Concerns`: `Models\Concerns\HasAssociations`
  - `RefactorCircus\Showroom\Domains\Asset\Policies`: `Policies\AssetPolicy`
  - `RefactorCircus\Showroom\Domains\Association\Policies`: `Policies\AssociationTypePolicy`
  - `RefactorCircus\Showroom\Domains\Attribute\Policies`: `Policies\AttributeGroupPolicy`, `Policies\AttributeOptionPolicy`, `Policies\AttributePolicy`
  - `RefactorCircus\Showroom\Domains\Category\Policies`: `Policies\CategoryPolicy`
  - `RefactorCircus\Showroom\Domains\Channel\Policies`: `Policies\ChannelPolicy`, `Policies\LocalePolicy`
  - `RefactorCircus\Showroom\Domains\Family\Policies`: `Policies\FamilyPolicy`, `Policies\FamilyVariantPolicy`
  - `RefactorCircus\Showroom\Domains\Owner\Policies`: `Policies\OwnerPolicy`, `Policies\OwnerTypePolicy`
  - `RefactorCircus\Showroom\Support\Policies`: `Policies\Policy`
  - `RefactorCircus\Showroom\Domains\ProductModel\Policies`: `Policies\ProductModelPolicy`
  - `RefactorCircus\Showroom\Domains\Product\Policies`: `Policies\ProductPolicy`
  - `RefactorCircus\Showroom\Domains\Search\Data`: `Search\Complete`, `Search\Filter`, `Search\ProductQuery`, `Search\SearchResults`
  - `RefactorCircus\Showroom\Domains\Search\Services`: `Search\Engines\DatabaseEngine`, `Search\Engines\ElasticsearchEngine`, `Search\Engines\ScoutEngine`, `Search\ProductDocument`, `Search\ProductIndex`
  - `RefactorCircus\Showroom\Domains\Search\Support`: `Search\Engines\SqlFragment`, `Search\ProductPage`
  - `RefactorCircus\Showroom\Domains\Search\Models`: `Search\Scout\SearchableProduct` (as `SearchableProductModel`)
  - `RefactorCircus\Showroom\Domains\Attribute\Services`: `Values\UniqueValues`, `Values\ValueValidator`, `Values\Values`
  - `RefactorCircus\Showroom\Domains\Attribute\Data`: `Values\ValueFilter`
  - `RefactorCircus\Showroom\Domains\Workflow\Services`: `Workflow\CompletenessCalculator`, `Workflow\Versions`

### Fixed

- Every class the Atrium screens use exists in Atrium's stylesheet, so all of them take effect.

### Added

- With refactor-circus/keen, the product, product model, family, attribute, category, owner, asset and channel pages show the record's audit history, and the product list shows Showroom's (`<x-atrium::audit-trail>`). Product versions and revert stay.
- Audit labels (`RefactorCircus\Keystone\Audit\AuditHooks`): records with localized labels are named by the current locale's label (else the code), associations by their type and both ends, and versions by what they version and their number.
- `GET /showroom/history` (`showroom.history.index`) and the `list-showroom-history-tool` MCP tool list Showroom's audit history, newest first, when [refactor-circus/keen](https://github.com/Refactor-Circus/Keen) is installed. Without it, both answer that no audit log is installed (`404` over HTTP).
- Catalog foundation: Actions shared by the HTTP API, MCP server and Atrium dashboard, with starting/finished action events and per-hook model events.
- Attribute groups, typed attributes (text, textarea, number, decimal, boolean, date, select, multiselect, price, metric) and attribute options.
- JSON API under `/showroom`, MCP tools behind ToolSearch, optional Cortex registration, and Atrium catalog pages, settings and search.
- Config-driven authorization and swappable policies.
- Families (attribute sets): attribute membership with required flags and order, and a text label attribute. Deleting an attribute that labels a family is refused.
- Products with typed, localizable and scopable values stored as JSON, validated per attribute type and settings; unique attributes enforced by a database index.
- Family variants (one or two levels of axes), product models and variant products with value inheritance and distinct axis combinations.
- Pluggable product search: database (default), Elasticsearch via `refactor-circus/stretch`, or any Laravel Scout engine; queued index syncing and `showroom:search:reindex`.
- Deleting an attribute or option purges its stored values in a queued job.
- Atrium dashboard widgets: products by status, completeness per channel and locale, review queue and recent changes.
- Import, export and feeds with `refactor-circus/impex` (optional): `showroom:import-products` (CSV/JSONL, create/update/upsert, per-row failures), `showroom:upsert-products` for ERP pushes through inbound channels, resumable `showroom:export-products` of any search (working or published versions), per-channel `showroom:feed:{name}` flows with ledgered HTTP delivery; start endpoints, MCP tools and an Atrium page.
- Completeness per channel and locale (with per-channel requirements), stored and searchable; workflow statuses and transitions with snapshot publishing; versioning with authors, changes, history and revert.
- Associations: runtime association types (plain, two-way with automatic mirroring, quantified for bundles and kits); associations on products and product models, inherited by variants; cleanup on delete.
- Channels and locales: locales and channels (locales, currencies, category tree); localizable and scopable values enforced against them, including a channel's own locales and currencies; `scope` and `locales` on product reads; purging a deleted locale's or channel's values; dashboard editing of every value slot.
- Media: assets on any filesystem disk (streamed; S3/Vapor-ready), added by upload, disk path or URL with size and type limits; linked to products, product models and owners under roles, inherited by variants; temporary URLs for private disks.
- Taxonomy: independent category trees with moves, products and product models filed in categories (inherited by variants), product search by category branch.
- Dynamic hierarchical ownership: runtime owner types with parent, root and product rules; owner chains of any depth with moves; products and root product models assigned to owners; product search by owner subtree.

### Changed

- Requires PHP 8.5 (`php: ^8.5`); CI runs on PHP 8.5 only. Dependency floors raised to the current releases: laravel/framework ^13.35, orchestra/testbench ^11.3, pestphp/pest ^5.3.1, larastan/larastan ^3.13 and laravel/scout ^11.9.
- Atrium screens follow Atrium's screen conventions: every action is an icon button (tabs and back links too), product, enabled, live and import/export statuses are status dots coloured by `RefactorCircus\Showroom\Atrium\Badges` (`info` only for in review and pending or waiting runs), and every navigation item has an icon. `ProductStatus::badge()` now answers from `Badges`: in review is `info` and draft `primary`.
- Atrium screens now authorize every page and action against `showroom.policies`, asked exactly as the JSON API and MCP tools ask, and show each navigation item, button, form and card only when its action would be allowed (`ScreenAccess`, `@showroomCan`). Widgets and search are gated the same way. With `showroom.authorization` on (the default) a guest sees nothing; turn it off for an open operator dashboard.
- Requires refactor-circus/atrium with icon buttons and status dots (f5eb488 or later).

### Added

- `ShowroomSupportFeature` (with refactor-circus/pennantplus): switches Showroom in Atrium on and off as a whole through Pennant; `showroom.atrium.features` names the features checked, skipping classes that are not installed.

### Removed

- Skeleton placeholder config, route, migration, view, command, translation and public assets tag.


## [v0.1.0](https://github.com/Refactor-Circus/Showroom/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
