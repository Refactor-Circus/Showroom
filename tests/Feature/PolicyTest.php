<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Asset\Policies\AssetPolicy;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationModel;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;
use RefactorCircus\Showroom\Domains\Association\Policies\AssociationPolicy;
use RefactorCircus\Showroom\Domains\Association\Policies\AssociationTypePolicy;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\CreateAttributeTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\ListAttributesTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\ShowAttributeTool;
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
use RefactorCircus\Showroom\ShowroomServiceProvider;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;
use RefactorCircus\Showroom\Tests\Fixtures\ReadOnlyAttributePolicy;
use RefactorCircus\Showroom\Tests\Fixtures\ReadOnlyProductPolicy;
use Workbench\App\Models\User;

beforeEach(function (): void {
    config()->set('showroom.authorization', true);

    $this->ann = User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']);
});

/**
 * Register the policies again after a test changes `showroom.policies`, as
 * the provider does on boot.
 *
 * @param  array<class-string, class-string>  $policies
 */
function useShowroomPolicies(array $policies): void
{
    foreach ($policies as $model => $policy) {
        config()->set('showroom.policies.'.$model, $policy);
    }

    $provider = app()->getProvider(ShowroomServiceProvider::class);

    (fn () => $this->registerPolicies())->call($provider);
}

it('registers the policies from the config', function (): void {
    expect(Gate::getPolicyFor(AttributeGroupModel::class))->toBeInstanceOf(AttributeGroupPolicy::class)
        ->and(Gate::getPolicyFor(AttributeModel::class))->toBeInstanceOf(AttributePolicy::class)
        ->and(Gate::getPolicyFor(AttributeOptionModel::class))->toBeInstanceOf(AttributeOptionPolicy::class)
        ->and(Gate::getPolicyFor(FamilyModel::class))->toBeInstanceOf(FamilyPolicy::class)
        ->and(Gate::getPolicyFor(FamilyVariantModel::class))->toBeInstanceOf(FamilyVariantPolicy::class)
        ->and(Gate::getPolicyFor(ProductModelModel::class))->toBeInstanceOf(ProductModelPolicy::class)
        ->and(Gate::getPolicyFor(ProductModel::class))->toBeInstanceOf(ProductPolicy::class)
        ->and(Gate::getPolicyFor(OwnerTypeModel::class))->toBeInstanceOf(OwnerTypePolicy::class)
        ->and(Gate::getPolicyFor(OwnerModel::class))->toBeInstanceOf(OwnerPolicy::class)
        ->and(Gate::getPolicyFor(CategoryModel::class))->toBeInstanceOf(CategoryPolicy::class)
        ->and(Gate::getPolicyFor(AssetModel::class))->toBeInstanceOf(AssetPolicy::class)
        ->and(Gate::getPolicyFor(LocaleModel::class))->toBeInstanceOf(LocalePolicy::class)
        ->and(Gate::getPolicyFor(ChannelModel::class))->toBeInstanceOf(ChannelPolicy::class)
        ->and(Gate::getPolicyFor(AssociationTypeModel::class))->toBeInstanceOf(AssociationTypePolicy::class)
        ->and(Gate::getPolicyFor(AssociationModel::class))->toBeInstanceOf(AssociationPolicy::class)
        ->and(Gate::getPolicyFor(VersionModel::class))->toBeInstanceOf(VersionPolicy::class)
        ->and(Gate::getPolicyFor(CompletenessModel::class))->toBeInstanceOf(CompletenessPolicy::class)
        ->and(Gate::getPolicyFor(SearchableProductModel::class))->toBeInstanceOf(SearchableProductPolicy::class);
});

it('refuses a guest once authorization is on', function (): void {
    $this->getJson('/showroom/attributes')->assertUnauthorized();
    $this->postJson('/showroom/attributes', ['code' => 'color', 'type' => 'select'])->assertUnauthorized();
    mcpTool(ListAttributesTool::class)->assertHasErrors(['Unauthorized.']);
});

it('lets an authenticated user manage the catalog by default', function (): void {
    $this->actingAs($this->ann);

    $this->postJson('/showroom/attributes', ['code' => 'color', 'type' => 'select'])->assertCreated();
    $this->getJson('/showroom/attributes/color')->assertOk();
    mcpTool(ShowAttributeTool::class, ['attribute' => 'color'])->assertOk();
});

it('leaves the API to the route middleware while authorization is off', function (): void {
    config()->set('showroom.authorization', false);

    AttributeModel::factory()->create(['code' => 'name']);

    $this->getJson('/showroom/attributes/name')->assertOk();
    mcpTool(ShowAttributeTool::class, ['attribute' => 'name'])->assertOk();
});

it('applies a policy swapped in through the config', function (): void {
    useShowroomPolicies([AttributeModel::class => ReadOnlyAttributePolicy::class]);

    $this->actingAs($this->ann);
    AttributeModel::factory()->create(['code' => 'name']);

    $this->getJson('/showroom/attributes/name')->assertOk();
    $this->postJson('/showroom/attributes', ['code' => 'color', 'type' => 'select'])->assertForbidden();
    $this->patchJson('/showroom/attributes/name', ['labels' => ['en' => 'Name']])->assertForbidden();
    $this->deleteJson('/showroom/attributes/name')->assertForbidden();
    mcpTool(CreateAttributeTool::class, ['code' => 'color', 'type' => 'select'])->assertHasErrors(['Unauthorized.']);
});

it('lets associations, versions, completeness and searchable products follow the product policy', function (): void {
    $this->actingAs($this->ann);
    Catalog::apparel();
    $this->postJson('/showroom/association-types', ['code' => 'cross_sell'])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'CAP'])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'TEE', 'family' => 'shirts', 'associations' => ['cross_sell' => ['products' => ['CAP']]]])->assertCreated();
    $this->postJson('/showroom/products/TEE/transitions', ['transition' => 'submit'])->assertOk();

    $tee = ProductModel::query()->where('identifier', 'TEE')->sole();
    $children = [
        AssociationModel::query()->sole(),
        VersionModel::query()->where('versionable_id', $tee->id)->firstOrFail(),
        CompletenessModel::query()->where('product_id', $tee->id)->firstOrFail(),
        (new SearchableProductModel)->newFromBuilder($tee->getAttributes()),
    ];

    foreach ($children as $child) {
        expect($this->ann->can('view', $child))->toBeTrue()
            ->and($this->ann->can('update', $child))->toBeTrue();
    }

    useShowroomPolicies([ProductModel::class => ReadOnlyProductPolicy::class]);

    foreach ($children as $child) {
        expect($this->ann->can('view', $child))->toBeTrue()
            ->and($this->ann->can('update', $child))->toBeFalse()
            ->and($this->ann->can('delete', $child))->toBeFalse();
    }

    expect($this->ann->can('viewAny', SearchableProductModel::class))->toBeTrue()
        ->and($this->ann->can('create', SearchableProductModel::class))->toBeFalse();
});
