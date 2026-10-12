<?php

declare(strict_types=1);

use RefactorCircus\Showroom\Atrium\Features\ShowroomSupportFeature;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Asset\Policies\AssetPolicy;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationModel;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;
use RefactorCircus\Showroom\Domains\Association\Policies\AssociationPolicy;
use RefactorCircus\Showroom\Domains\Association\Policies\AssociationTypePolicy;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;
use RefactorCircus\Showroom\Domains\Attribute\Policies\AttributeGroupPolicy;
use RefactorCircus\Showroom\Domains\Attribute\Policies\AttributeOptionPolicy;
use RefactorCircus\Showroom\Domains\Attribute\Policies\AttributePolicy;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Category\Policies\CategoryPolicy;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;
use RefactorCircus\Showroom\Domains\Channel\Policies\ChannelPolicy;
use RefactorCircus\Showroom\Domains\Channel\Policies\LocalePolicy;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;
use RefactorCircus\Showroom\Domains\Family\Policies\FamilyPolicy;
use RefactorCircus\Showroom\Domains\Family\Policies\FamilyVariantPolicy;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Owner\Policies\OwnerPolicy;
use RefactorCircus\Showroom\Domains\Owner\Policies\OwnerTypePolicy;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Product\Policies\ProductPolicy;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\ProductModel\Policies\ProductModelPolicy;
use RefactorCircus\Showroom\Domains\Search\Models\SearchableProductModel;
use RefactorCircus\Showroom\Domains\Search\Policies\SearchableProductPolicy;
use RefactorCircus\Showroom\Domains\Workflow\Models\CompletenessModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;
use RefactorCircus\Showroom\Domains\Workflow\Policies\CompletenessPolicy;
use RefactorCircus\Showroom\Domains\Workflow\Policies\VersionPolicy;
use RefactorCircus\Showroom\Impex\Webhooks\ProductStream;

return [

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | With authorization on, the JSON API, the MCP tools and the Atrium
    | screens require an authenticated user and check every call against the
    | policies below; the screens show only the navigation and controls the
    | user may use. Turn it off only for a trusted operator surface, where
    | the route middleware (and Atrium's own gate) is the only check.
    |
    */

    'authorization' => true,

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | The policy the Gate uses for each Showroom model. The bundled policies
    | let any authenticated user manage the catalog; point a model at a class
    | of your own (extending the bundled one, or not) to restrict who may read
    | or change it.
    |
    | Records that belong to another follow its policy: an association its
    | source product or product model, a version and a completeness score
    | their product (`view` for reading, `update` for changing), and the
    | searchable product Scout indexes the product policy. Every model has an
    | entry; remove one to leave that model without a policy.
    |
    */

    'policies' => [
        AttributeGroupModel::class => AttributeGroupPolicy::class,
        AttributeModel::class => AttributePolicy::class,
        AttributeOptionModel::class => AttributeOptionPolicy::class,
        FamilyModel::class => FamilyPolicy::class,
        FamilyVariantModel::class => FamilyVariantPolicy::class,
        ProductModelModel::class => ProductModelPolicy::class,
        ProductModel::class => ProductPolicy::class,
        OwnerTypeModel::class => OwnerTypePolicy::class,
        OwnerModel::class => OwnerPolicy::class,
        CategoryModel::class => CategoryPolicy::class,
        AssetModel::class => AssetPolicy::class,
        LocaleModel::class => LocalePolicy::class,
        ChannelModel::class => ChannelPolicy::class,
        AssociationTypeModel::class => AssociationTypePolicy::class,
        AssociationModel::class => AssociationPolicy::class,
        VersionModel::class => VersionPolicy::class,
        CompletenessModel::class => CompletenessPolicy::class,
        SearchableProductModel::class => SearchableProductPolicy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    |
    | The table each of Showroom's records is stored in. Set these before
    | running the migrations: the migrations, models, validation rules, the
    | database search engine and the webhook queries all read them. A
    | published config that lists only some keeps the defaults for the rest.
    |
    */

    'tables' => [
        'attribute_groups' => 'showroom_attribute_groups',
        'attributes' => 'showroom_attributes',
        'attribute_options' => 'showroom_attribute_options',
        'families' => 'showroom_families',
        'family_attributes' => 'showroom_family_attributes',
        'family_variants' => 'showroom_family_variants',
        'family_variant_attributes' => 'showroom_family_variant_attributes',
        'product_models' => 'showroom_product_models',
        'products' => 'showroom_products',
        'product_unique_values' => 'showroom_product_unique_values',
        'owner_types' => 'showroom_owner_types',
        'owner_type_parents' => 'showroom_owner_type_parents',
        'owners' => 'showroom_owners',
        'categories' => 'showroom_categories',
        'category_product' => 'showroom_category_product',
        'category_product_model' => 'showroom_category_product_model',
        'assets' => 'showroom_assets',
        'asset_links' => 'showroom_asset_links',
        'locales' => 'showroom_locales',
        'channels' => 'showroom_channels',
        'channel_locale' => 'showroom_channel_locale',
        'association_types' => 'showroom_association_types',
        'associations' => 'showroom_associations',
        'product_completeness' => 'showroom_product_completeness',
        'versions' => 'showroom_versions',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP API Routes
    |--------------------------------------------------------------------------
    |
    | The prefix and middleware applied to the Showroom API routes. Add
    | authentication middleware before exposing these in production — they
    | change the catalog every product depends on.
    |
    */

    'routes' => [
        'enabled' => true,
        'prefix' => 'showroom',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Server
    |--------------------------------------------------------------------------
    |
    | Showroom exposes the same operations over MCP as over HTTP: both
    | surfaces call one Action, so they cannot drift. Both transports ship
    | disabled. When enabling the web transport, add auth middleware.
    |
    */

    'mcp' => [
        'web' => [
            'enabled' => false,
            'route' => 'mcp/showroom',
            'middleware' => [],
        ],
        'local' => [
            'enabled' => false,
            'handle' => 'showroom',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cortex
    |--------------------------------------------------------------------------
    |
    | When refactor-circus/cortex is installed, the MCP server is registered with it, so
    | its instructions can be overridden, and the tools join its registry,
    | so Cortex agents can manage the catalog. Set `tools` to a list of tool
    | names, such as ['list-attributes-tool', 'show-attribute-tool'], to
    | offer only some of them.
    |
    */

    'cortex' => [
        'enabled' => true,
        'server' => 'showroom',
        'tools' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard UI
    |--------------------------------------------------------------------------
    |
    | Showroom renders its dashboard through Atrium, which owns the path,
    | middleware and authorization gate.
    |
    */

    'ui' => [

        /*
        | Whether Showroom registers itself with the Atrium dashboard. The JSON
        | API is unaffected by this switch.
        */

        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Atrium
    |--------------------------------------------------------------------------
    |
    | features: Features that must all be on for Showroom to appear in
    |           Atrium at all - its navigation, widgets, settings, search and
    |           pages (which answer 404 otherwise). Atrium asks its feature
    |           resolver, so Pennant (through refactor-circus/pennantplus) or any other
    |           flag system decides.
    |
    |           ShowroomSupportFeature is on until its global value is set,
    |           and only its global value counts. Swap in a subclass to change
    |           that, or your own feature names. Feature classes that do not
    |           exist (without refactor-circus/pennantplus) are skipped, so nothing is
    |           checked until Pennant is installed. Empty always shows
    |           Showroom.
    |
    | Individual pages and controls are still shown per Showroom policy.
    |
    | - `widget_cache_seconds`: how long the completeness widget keeps its
    |   figures, which read every stored score. 0 reads them fresh on every
    |   render.
    |
    */

    'atrium' => [
        'features' => [
            ShowroomSupportFeature::class,
        ],
        'widget_cache_seconds' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflow
    |--------------------------------------------------------------------------
    |
    | Products move draft → in review → approved, and `publish` makes the
    | current version the one storefronts read.
    |
    | - `require_approval`: publish only approved products. Off, any product
    |   but an archived one can be published directly.
    | - `require_complete`: submit for review only products 100% complete on
    |   every channel and locale.
    |
    */

    'workflow' => [
        'require_approval' => true,
        'require_complete' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit
    |--------------------------------------------------------------------------
    |
    | Showroom keeps no audit log of its own. With refactor-circus/keen
    | installed, Keen records every change Showroom makes, and Showroom shows
    | the history on its Atrium pages, its `GET /showroom/history` route and
    | its history MCP tool. Without Keen none of that exists.
    |
    | enabled: Set to false to keep Showroom out of the audit log even while
    |          Keen is installed: Keen records nothing of Showroom's, and
    |          Showroom offers no history.
    |
    */

    'audit' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Import, Export and Feeds (Impex)
    |--------------------------------------------------------------------------
    |
    | With refactor-circus/impex installed, Showroom registers its flows:
    | `showroom:import-products`, `showroom:upsert-products`,
    | `showroom:export-products`, and `showroom:feed:{name}` for each feed.
    |
    | - `chunk`, `allow_failures` (a share, 0.02 = 2%), `tries`: how imports
    |   batch, how many rows may fail before the run fails, retries per row.
    |   By default every row may fail: the run completes and reports how many
    |   did, with each failed row's reason kept by Impex.
    | - `export_page_size`, `export_path`: how exports are paged and where
    |   their files go (on the media disk).
    | - `feeds`: syndication feeds, each a channel's published products:
    |   'google' => ['channel' => 'ecommerce', 'format' => 'jsonl',
    |                'url' => 'https://…', 'ledger_channel' => 'google-feed'],
    |   Schedule one with Impex: 'schedule' => ['showroom:feed:google' => '0 3 * * *'].
    |   Or name an outbound Impex channel with 'deliver_through' => 'google-sftp'
    |   to send the file the channel's way: its transport, signing and headers.
    |
    */

    'impex' => [
        'enabled' => true,
        'chunk' => 500,
        'allow_failures' => 1.0,
        'tries' => 1,
        'export_page_size' => 500,
        'export_path' => 'showroom/exports',
        'feeds' => [],

        /*
        | Product webhooks: published products as an Impex stream, so vendors
        | subscribe to the products and topics they want and are pushed
        | changes (or pull them from a feed) instead of polling the API.
        | Subscribers, endpoints and deliveries are managed through Impex.
        |
        | - `enabled`: off until someone subscribes; once on, every product
        |   write is compared with what subscribers last saw.
        | - `stream`: the stream's key. `stream_class` swaps the stream.
        | - `topics`: what a subscriber picks from, in a fixed order — append
        |   new ones, never reorder or remove one. A topic claims attribute
        |   values by `types` or attribute `groups`, product `fields`, and
        |   linked `assets`; the `default` topic takes the rest.
        | - `formatters`: extra payload formats, name => Formatter class,
        |   beside thin, slice and full.
        */

        'webhooks' => [
            'enabled' => false,
            'stream' => 'showroom.products',
            'stream_class' => ProductStream::class,
            'topics' => [
                'content' => ['default' => true],
                'pricing' => ['types' => ['price']],
                'assets' => ['assets' => true],
                'resources' => ['groups' => []],
                'catalog' => ['fields' => ['family', 'parent', 'owner', 'enabled', 'categories', 'associations', 'quantified_associations']],
            ],
            'formatters' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    |
    | Where asset files live. Any Laravel filesystem disk works; use S3 (or
    | another object store) on Vapor or Lambda, where the local disk does not
    | outlive the request. Files are streamed, never held whole in memory.
    |
    | - `disk`: a disk from config/filesystems.php; null for the default.
    | - `path`: the folder assets are written under.
    | - `max_kilobytes`: the largest file accepted.
    | - `mime_types`: allowed MIME types, or null for any.
    | - `temporary_urls`: minutes a signed URL lasts, for private buckets;
    |   null serves plain URLs.
    | - `delete_files`: whether deleting an asset deletes its file.
    | - `download_timeout`: seconds allowed to fetch an asset from a URL.
    |
    */

    'media' => [
        'disk' => null,
        'path' => 'showroom/assets',
        'max_kilobytes' => 51200,
        'mime_types' => null,
        'temporary_urls' => null,
        'delete_files' => true,
        'download_timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Search
    |--------------------------------------------------------------------------
    |
    | Where products are searched and listed.
    |
    | - null: "scout" when laravel/scout is installed, "database" otherwise.
    | - "database": no extra service; reads the products table. Fine for
    |   thousands of products.
    | - "scout": whichever Laravel Scout engine `scout.driver` names —
    |   Meilisearch, Typesense, Algolia, or a community driver such as one
    |   for Elasticsearch.
    | - or the class name of your own RefactorCircus\Showroom\Domains\Search\Contracts\SearchEngine.
    |
    | Writes are synced to the index by a queued job after each commit.
    | Rebuild it with `php artisan showroom:search:reindex`.
    |
    */

    'search' => [
        'engine' => null,

        'queue' => [
            'connection' => null,
            'queue' => null,
        ],

        'scout' => [
            'index' => 'showroom_products',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Page sizes for every listing, over HTTP, MCP and the dashboard. Callers
    | may ask for `per_page` up to the maximum.
    |
    */

    'pagination' => [
        'per_page' => 25,
        'max_per_page' => 100,
    ],

];
