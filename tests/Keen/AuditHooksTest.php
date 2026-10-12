<?php

declare(strict_types=1);

use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

/**
 * What the audit log (refactor-circus/keen) calls Showroom's records, where the default
 * naming attributes would fall short.
 */
beforeEach(function (): void {
    Catalog::apparel();
});

it('names labelled records by the current locale label, else the code', function (): void {
    $hooks = app(AuditHooks::class);

    $this->patchJson('/showroom/families/shirts', ['labels' => ['en' => 'Shirts', 'fr' => 'Chemises']])->assertOk();

    expect($hooks->labelFor(FamilyModel::query()->where('code', 'shirts')->firstOrFail()))->toBe('Shirts')
        ->and($hooks->labelFor(AttributeModel::query()->where('code', 'color')->firstOrFail()))->toBe('color');

    app()->setLocale('fr');

    expect($hooks->labelFor(FamilyModel::query()->where('code', 'shirts')->firstOrFail()))->toBe('Chemises');
});

it('names an association by its type and both ends', function (): void {
    $this->postJson('/showroom/association-types', ['code' => 'cross_sell', 'labels' => ['en' => 'Cross-sell']])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'TEE'])->assertCreated();
    $this->postJson('/showroom/product-models', ['code' => 'polo', 'family_variant' => 'shirts_by_size'])->assertCreated();
    $this->patchJson('/showroom/products/TEE', ['associations' => ['cross_sell' => ['product_models' => ['polo']]]])->assertOk();

    expect(app(AuditHooks::class)->labelFor(AssociationModel::query()->firstOrFail()))->toBe('Cross-sell: TEE → polo');
});

it('names a version by what it versions and its number', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'TEE'])->assertCreated();
    $this->patchJson('/showroom/products/TEE', ['enabled' => false])->assertOk();

    $version = VersionModel::query()->firstOrFail();

    expect(app(AuditHooks::class)->labelFor($version))->toBe($version->versionable_type.' v'.$version->version);
});
