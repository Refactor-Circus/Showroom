<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\Search\Enums\SyncScope;
use RefactorCircus\Showroom\Impex\ImpexIntegration;

/*
 * Values stored by name - the short morph aliases, the classes Impex records
 * for Showroom's flows, queued jobs - must keep resolving.
 */

it('keeps the morph aliases products, product models and owners are stored under', function (string $alias, string $model): void {
    expect(Relation::getMorphedModel($alias))->toBe($model)
        ->and((new $model)->getMorphClass())->toBe($alias);
})->with([
    ['showroom_product', ProductModel::class],
    ['showroom_product_model', ProductModelModel::class],
    ['showroom_owner', OwnerModel::class],
]);

it('keeps the class names Impex stores for Showroom flows, sources and actions', function (): void {
    // Impex stores these on its runs (flow_class) and batches (source, action).
    expect(app(ImpexIntegration::class)->flows())->toMatchArray([
        ImpexIntegration::IMPORT => 'RefactorCircus\\Showroom\\Impex\\Flows\\ImportProductsFlow',
        ImpexIntegration::UPSERT => 'RefactorCircus\\Showroom\\Impex\\Flows\\UpsertProductsFlow',
        ImpexIntegration::EXPORT => 'RefactorCircus\\Showroom\\Impex\\Flows\\ExportProductsFlow',
    ]);

    foreach ([
        'RefactorCircus\\Showroom\\Impex\\Flows\\FeedFlow',
        'RefactorCircus\\Showroom\\Impex\\Sources\\FileProductSource',
        'RefactorCircus\\Showroom\\Impex\\Sources\\InlineProductSource',
        'RefactorCircus\\Showroom\\Impex\\Actions\\DeliverFeed',
        'RefactorCircus\\Showroom\\Impex\\Actions\\ExportProductPages',
        'RefactorCircus\\Showroom\\Impex\\Actions\\ImportProductRow',
        'RefactorCircus\\Showroom\\Impex\\Actions\\JoinProductExport',
    ] as $class) {
        expect(class_exists($class))->toBeTrue($class);
    }
});

it('keeps the class names of queued jobs', function (string $job): void {
    expect(class_exists($job))->toBeTrue();
})->with([
    'RefactorCircus\\Showroom\\Jobs\\PurgeAttributeValues',
    'RefactorCircus\\Showroom\\Jobs\\PurgeValueSlots',
    'RefactorCircus\\Showroom\\Jobs\\QueueProductSyncs',
    'RefactorCircus\\Showroom\\Jobs\\SyncProductIndex',
]);

it('keeps the stored values of sync scopes', function (): void {
    expect(array_column(SyncScope::cases(), 'value'))->toBe(['all', 'family', 'owner', 'category']);
});
