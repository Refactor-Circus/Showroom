<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Support;

use RefactorCircus\Keystone\Database\Tables as KeystoneTables;

/**
 * The table each of Showroom's records lives in, from the `showroom.tables`
 * config key. Models, migrations, validation rules, the database search
 * engine and the webhook queries all ask here, so renaming a table is one
 * config change made before the migrations run.
 *
 * A published config that lists only some tables keeps the defaults for the
 * rest.
 */
final class Tables extends KeystoneTables
{
    protected const string CONFIG = 'showroom.tables';

    /**
     * @var array<string, string>
     */
    public const array DEFAULTS = [
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
    ];
}
