<?php

declare(strict_types=1);

use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Search\Models\SearchableProductModel;
use RefactorCircus\Showroom\Support\Tables;

it('ships the default table names in its config', function (): void {
    $config = require dirname(__DIR__, 2).'/config/showroom.php';

    expect($config['tables'])->toBe(Tables::DEFAULTS);
});

it('keeps the default for tables a published config leaves out', function (): void {
    config()->set('showroom.tables', ['products' => 'crm_products']);

    expect(Tables::name('products'))->toBe('crm_products')
        ->and(Tables::name('families'))->toBe('showroom_families')
        ->and((new ProductModel)->getTable())->toBe('crm_products')
        ->and((new SearchableProductModel)->getTable())->toBe('crm_products');

    // The migrations roll back after the test, under the names they ran with.
    config()->set('showroom.tables', Tables::DEFAULTS);
});
