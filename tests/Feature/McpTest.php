<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Tools\AttachAssetTool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Tools\CreateAssetTool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Tools\DeleteAssetTool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Tools\DetachAssetTool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Tools\ListAssetsTool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Tools\ShowAssetTool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Tools\UpdateAssetTool;
use RefactorCircus\Showroom\Domains\Association\Mcp\Tools\CreateAssociationTypeTool;
use RefactorCircus\Showroom\Domains\Association\Mcp\Tools\DeleteAssociationTypeTool;
use RefactorCircus\Showroom\Domains\Association\Mcp\Tools\ListAssociationTypesTool;
use RefactorCircus\Showroom\Domains\Association\Mcp\Tools\ShowAssociationTypeTool;
use RefactorCircus\Showroom\Domains\Association\Mcp\Tools\UpdateAssociationTypeTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\CreateAttributeGroupTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\CreateAttributeOptionTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\CreateAttributeTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\DeleteAttributeGroupTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\DeleteAttributeOptionTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\DeleteAttributeTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\ListAttributeGroupsTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\ListAttributeOptionsTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\ListAttributesTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\ShowAttributeGroupTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\ShowAttributeTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\UpdateAttributeGroupTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\UpdateAttributeOptionTool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools\UpdateAttributeTool;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;
use RefactorCircus\Showroom\Domains\Category\Mcp\Tools\CreateCategoryTool;
use RefactorCircus\Showroom\Domains\Category\Mcp\Tools\DeleteCategoryTool;
use RefactorCircus\Showroom\Domains\Category\Mcp\Tools\ListCategoriesTool;
use RefactorCircus\Showroom\Domains\Category\Mcp\Tools\ShowCategoryTool;
use RefactorCircus\Showroom\Domains\Category\Mcp\Tools\UpdateCategoryTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\CreateChannelTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\CreateLocaleTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\DeleteChannelTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\DeleteLocaleTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\ListChannelsTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\ListLocalesTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\ShowChannelTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\ShowLocaleTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\UpdateChannelTool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Tools\UpdateLocaleTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\CreateFamilyTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\CreateFamilyVariantTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\DeleteFamilyTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\DeleteFamilyVariantTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\ListFamiliesTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\ListFamilyVariantsTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\ShowFamilyTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\ShowFamilyVariantTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\UpdateFamilyTool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Tools\UpdateFamilyVariantTool;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\CreateOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\CreateOwnerTypeTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\DeleteOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\DeleteOwnerTypeTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\ListOwnersTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\ListOwnerTypesTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\ShowOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\ShowOwnerTypeTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\UpdateOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\UpdateOwnerTypeTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\CreateProductTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\DeleteProductTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ListProductsTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ShowProductTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\UpdateProductTool;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Mcp\Tools\CreateProductModelTool;
use RefactorCircus\Showroom\Domains\ProductModel\Mcp\Tools\DeleteProductModelTool;
use RefactorCircus\Showroom\Domains\ProductModel\Mcp\Tools\ListProductModelsTool;
use RefactorCircus\Showroom\Domains\ProductModel\Mcp\Tools\ShowProductModelTool;
use RefactorCircus\Showroom\Domains\ProductModel\Mcp\Tools\UpdateProductModelTool;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools\ListProductVersionsTool;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools\RevertProductTool;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools\ShowProductVersionTool;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools\TransitionProductTool;
use RefactorCircus\Showroom\Mcp\ShowroomServer;
use RefactorCircus\Showroom\Mcp\Tools\ListShowroomHistoryTool;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

it('offers one tool per use case', function (): void {
    expect(ShowroomServer::TOOLS)->toHaveCount(78);
});

it('declares its history tool, but offers it only while Showroom audits', function (): void {
    expect(ShowroomServer::TOOLS)->toContain(ListShowroomHistoryTool::class)
        ->and(app(ListShowroomHistoryTool::class)->name())->toBe('list-showroom-history-tool')
        ->and(app(PackageRegistry::class)->get('showroom')->audits())->toBeFalse();

    mcpTool(ListShowroomHistoryTool::class)->assertHasErrors();
});

it('manages attribute groups', function (): void {
    mcpTool(CreateAttributeGroupTool::class, ['code' => 'technical', 'labels' => ['en' => 'Technical']])
        ->assertOk()
        ->assertSee('"code":"technical"');

    mcpTool(ListAttributeGroupsTool::class)->assertOk()->assertSee('technical');
    mcpTool(ShowAttributeGroupTool::class, ['group' => 'technical'])->assertOk()->assertSee('Technical');

    mcpTool(UpdateAttributeGroupTool::class, ['group' => 'technical', 'labels' => ['en' => 'Specs']])
        ->assertOk()
        ->assertSee('Specs');

    mcpTool(DeleteAttributeGroupTool::class, ['group' => 'technical'])->assertOk()->assertSee('"deleted":true');

    expect(AttributeGroupModel::query()->count())->toBe(0);
});

it('manages attributes', function (): void {
    AttributeGroupModel::factory()->create(['code' => 'marketing']);

    mcpTool(CreateAttributeTool::class, ['code' => 'headline', 'type' => 'text', 'group' => 'marketing', 'settings' => ['max_length' => 90]])
        ->assertOk()
        ->assertSee('"group":"marketing"');

    mcpTool(ListAttributesTool::class, ['type' => 'text'])->assertOk()->assertSee('headline');
    mcpTool(ShowAttributeTool::class, ['attribute' => 'headline'])->assertOk()->assertSee('"max_length":90');

    mcpTool(UpdateAttributeTool::class, ['attribute' => 'headline', 'group' => null, 'is_localizable' => true])
        ->assertOk()
        ->assertSee('"is_localizable":true');

    mcpTool(DeleteAttributeTool::class, ['attribute' => 'headline'])->assertOk();

    expect(AttributeModel::query()->count())->toBe(0);
});

it('manages attribute options', function (): void {
    AttributeModel::factory()->select()->create(['code' => 'color']);

    mcpTool(CreateAttributeOptionTool::class, ['attribute' => 'color', 'code' => 'red', 'labels' => ['en' => 'Red']])
        ->assertOk()
        ->assertSee('"code":"red"');

    mcpTool(ListAttributeOptionsTool::class, ['attribute' => 'color'])->assertOk()->assertSee('red');

    mcpTool(UpdateAttributeOptionTool::class, ['attribute' => 'color', 'option' => 'red', 'labels' => ['en' => 'Crimson']])
        ->assertOk()
        ->assertSee('Crimson');

    mcpTool(DeleteAttributeOptionTool::class, ['attribute' => 'color', 'option' => 'red'])->assertOk();

    expect(AttributeOptionModel::query()->count())->toBe(0);
});

it('manages families', function (): void {
    AttributeModel::factory()->create(['code' => 'name']);
    AttributeModel::factory()->select()->create(['code' => 'size']);

    mcpTool(CreateFamilyTool::class, [
        'code' => 'shoes',
        'attributes' => [['attribute' => 'name', 'is_required' => true], ['attribute' => 'size']],
        'label_attribute' => 'name',
    ])->assertOk()->assertSee('"label_attribute":"name"');

    mcpTool(ListFamiliesTool::class, ['attribute' => 'size'])->assertOk()->assertSee('shoes');
    mcpTool(ShowFamilyTool::class, ['family' => 'shoes'])->assertOk()->assertSee('"is_required":true');

    mcpTool(UpdateFamilyTool::class, ['family' => 'shoes', 'attributes' => [['attribute' => 'size']]])
        ->assertHasErrors();

    mcpTool(UpdateFamilyTool::class, ['family' => 'shoes', 'attributes' => [['attribute' => 'size']], 'label_attribute' => null])
        ->assertOk();

    mcpTool(DeleteFamilyTool::class, ['family' => 'shoes'])->assertOk();

    expect(FamilyModel::query()->count())->toBe(0);
});

it('manages family variants, product models and products', function (): void {
    Catalog::apparel();

    mcpTool(CreateFamilyVariantTool::class, [
        'code' => 'shirts_by_tag',
        'family' => 'shirts',
        'levels' => [['axes' => ['organic'], 'attributes' => ['ean']]],
    ])->assertOk()->assertSee('"axes":["organic"]');

    mcpTool(ListFamilyVariantsTool::class, ['family' => 'shirts'])->assertOk()->assertSee('shirts_by_tag');
    mcpTool(ShowFamilyVariantTool::class, ['family_variant' => 'shirts_by_tag'])->assertOk()->assertSee('common_attributes');
    mcpTool(UpdateFamilyVariantTool::class, ['family_variant' => 'shirts_by_tag', 'labels' => ['en' => 'By tag']])->assertOk()->assertSee('By tag');

    mcpTool(CreateProductModelTool::class, [
        'code' => 'tee',
        'family_variant' => 'shirts_by_size',
        'values' => ['name' => Catalog::value('Classic tee')],
    ])->assertOk()->assertSee('"level":0');

    mcpTool(CreateProductTool::class, [
        'identifier' => 'TEE-M',
        'parent' => 'tee',
        'values' => ['size' => Catalog::value('m')],
    ])->assertOk()->assertSee('Classic tee');

    mcpTool(CreateProductTool::class, ['identifier' => 'TEE-M2', 'parent' => 'tee', 'values' => ['size' => Catalog::value('m')]])
        ->assertHasErrors();

    mcpTool(ListProductModelsTool::class, ['roots' => true])->assertOk()->assertSee('"code":"tee"');
    mcpTool(ShowProductModelTool::class, ['product_model' => 'tee'])->assertOk()->assertSee('TEE-M');
    mcpTool(UpdateProductModelTool::class, ['product_model' => 'tee', 'values' => ['name' => Catalog::value('Organic tee')]])->assertOk();

    mcpTool(ShowProductTool::class, ['product' => 'TEE-M'])->assertOk()->assertSee('Organic tee');
    mcpTool(UpdateProductTool::class, ['product' => 'TEE-M', 'enabled' => false])->assertOk()->assertSee('"enabled":false');

    mcpTool(ListProductsTool::class, [
        'filters' => [['attribute' => 'size', 'operator' => '=', 'value' => 'm']],
    ])->assertOk()->assertSee('"total":1')->assertSee('TEE-M');

    mcpTool(DeleteProductTool::class, ['product' => 'TEE-M'])->assertOk()->assertSee('"identifier":"TEE-M"');
    mcpTool(DeleteProductModelTool::class, ['product_model' => 'tee'])->assertOk();
    mcpTool(DeleteFamilyVariantTool::class, ['family_variant' => 'shirts_by_tag'])->assertOk();

    expect(ProductModel::query()->count())->toBe(0);
});

it('manages ownership', function (): void {
    mcpTool(CreateOwnerTypeTool::class, ['code' => 'vendor'])->assertOk()->assertSee('"parent_types":null');
    mcpTool(CreateOwnerTypeTool::class, ['code' => 'series', 'parent_types' => ['vendor'], 'can_be_root' => false])->assertOk();
    mcpTool(ListOwnerTypesTool::class)->assertOk()->assertSee('series');
    mcpTool(ShowOwnerTypeTool::class, ['owner_type' => 'series'])->assertOk()->assertSee('"parent_types":["vendor"]');
    mcpTool(UpdateOwnerTypeTool::class, ['owner_type' => 'series', 'labels' => ['en' => 'Series']])->assertOk();

    mcpTool(CreateOwnerTool::class, ['code' => 'acme', 'type' => 'vendor'])->assertOk();
    mcpTool(CreateOwnerTool::class, ['code' => 'classic', 'type' => 'series'])->assertHasErrors(['A series needs a parent owner.']);
    mcpTool(CreateOwnerTool::class, ['code' => 'classic', 'type' => 'series', 'parent' => 'acme'])->assertOk();
    mcpTool(ListOwnersTool::class, ['under' => 'acme'])->assertOk()->assertSee('classic');
    mcpTool(ShowOwnerTool::class, ['owner' => 'classic'])->assertOk()->assertSee('"chain":["acme","classic"]');
    mcpTool(UpdateOwnerTool::class, ['owner' => 'classic', 'labels' => ['en' => 'Classic']])->assertOk();
    mcpTool(DeleteOwnerTool::class, ['owner' => 'acme'])->assertHasErrors();
    mcpTool(DeleteOwnerTool::class, ['owner' => 'classic'])->assertOk();
    mcpTool(DeleteOwnerTypeTool::class, ['owner_type' => 'series'])->assertOk();
});

it('manages category trees', function (): void {
    mcpTool(CreateCategoryTool::class, ['code' => 'master'])->assertOk();
    mcpTool(CreateCategoryTool::class, ['code' => 'shirts', 'parent' => 'master'])->assertOk();
    mcpTool(ListCategoriesTool::class, ['roots' => true])->assertOk()->assertSee('master')->assertDontSee('"code":"shirts"');
    mcpTool(ShowCategoryTool::class, ['category' => 'shirts'])->assertOk()->assertSee('"chain":["master","shirts"]');
    mcpTool(UpdateCategoryTool::class, ['category' => 'shirts', 'parent' => null])->assertOk();
    mcpTool(CreateProductTool::class, ['identifier' => 'TEE', 'categories' => ['shirts']])->assertOk()->assertSee('"categories":["shirts"]');
    mcpTool(ListProductsTool::class, ['category' => 'shirts'])->assertOk()->assertSee('TEE');
    mcpTool(DeleteCategoryTool::class, ['category' => 'shirts'])->assertOk();
});

it('manages assets from URLs', function (): void {
    config()->set('showroom.media.disk', 'assets');
    Storage::fake('assets');
    Http::fake(['*' => Http::response('jpeg-bytes', 200, ['Content-Type' => 'image/jpeg'])]);

    mcpTool(CreateProductTool::class, ['identifier' => 'TEE'])->assertOk();

    mcpTool(CreateAssetTool::class, ['code' => 'hero', 'url' => 'https://cdn.example.test/hero.jpg'])->assertOk()->assertSee('"mime_type":"image/jpeg"');
    mcpTool(AttachAssetTool::class, ['asset' => 'hero', 'type' => 'product', 'target' => 'TEE', 'role' => 'image'])->assertOk()->assertSee('"role":"image"');
    mcpTool(ListAssetsTool::class, ['product' => 'TEE'])->assertOk()->assertSee('hero');
    mcpTool(ShowAssetTool::class, ['asset' => 'hero'])->assertOk()->assertSee('"target":"TEE"');
    mcpTool(UpdateAssetTool::class, ['asset' => 'hero', 'labels' => ['en' => 'Hero']])->assertOk()->assertSee('Hero');
    mcpTool(DetachAssetTool::class, ['asset' => 'hero', 'type' => 'product', 'target' => 'TEE'])->assertOk()->assertSee('"links":[]');
    mcpTool(DeleteAssetTool::class, ['asset' => 'hero'])->assertOk();
});

it('manages locales and channels', function (): void {
    mcpTool(CreateLocaleTool::class, ['code' => 'en_US'])->assertOk();
    mcpTool(CreateLocaleTool::class, ['code' => 'English'])->assertHasErrors();
    mcpTool(ListLocalesTool::class)->assertOk()->assertSee('en_US');
    mcpTool(UpdateLocaleTool::class, ['locale' => 'en_US', 'labels' => ['en_US' => 'English (US)']])->assertOk();
    mcpTool(ShowLocaleTool::class, ['locale' => 'en_US'])->assertOk()->assertSee('English (US)');

    mcpTool(CreateChannelTool::class, ['code' => 'web', 'locales' => ['en_US'], 'currencies' => ['USD']])->assertOk()->assertSee('"locales":["en_US"]');
    mcpTool(ListChannelsTool::class)->assertOk()->assertSee('web');
    mcpTool(ShowChannelTool::class, ['channel' => 'web'])->assertOk()->assertSee('"currencies":["USD"]');
    mcpTool(UpdateChannelTool::class, ['channel' => 'web', 'currencies' => ['USD', 'CAD']])->assertOk()->assertSee('CAD');

    mcpTool(DeleteLocaleTool::class, ['locale' => 'en_US'])->assertHasErrors(['Locale "en_US" is used by channel "web". Remove it from them first.']);
    mcpTool(DeleteChannelTool::class, ['channel' => 'web'])->assertOk();
    mcpTool(DeleteLocaleTool::class, ['locale' => 'en_US'])->assertOk();
});

it('manages association types and associations', function (): void {
    mcpTool(CreateAssociationTypeTool::class, ['code' => 'bundle', 'is_quantified' => true])->assertOk()->assertSee('"is_quantified":true');
    mcpTool(ListAssociationTypesTool::class)->assertOk()->assertSee('bundle');
    mcpTool(ShowAssociationTypeTool::class, ['association_type' => 'bundle'])->assertOk();
    mcpTool(UpdateAssociationTypeTool::class, ['association_type' => 'bundle', 'labels' => ['en' => 'Bundle']])->assertOk()->assertSee('Bundle');

    mcpTool(CreateProductTool::class, ['identifier' => 'TEE'])->assertOk();
    mcpTool(CreateProductTool::class, [
        'identifier' => 'SET',
        'quantified_associations' => ['bundle' => ['products' => [['identifier' => 'TEE', 'quantity' => 2]]]],
    ])->assertOk()->assertSee('"quantity":2');

    mcpTool(DeleteAssociationTypeTool::class, ['association_type' => 'bundle'])->assertHasErrors();
});

it('runs the workflow and reads history', function (): void {
    mcpTool(CreateProductTool::class, ['identifier' => 'TEE'])->assertOk()->assertSee('"status":"draft"');
    mcpTool(TransitionProductTool::class, ['product' => 'TEE', 'transition' => 'publish'])->assertHasErrors();
    mcpTool(TransitionProductTool::class, ['product' => 'TEE', 'transition' => 'submit'])->assertOk()->assertSee('"status":"in_review"');
    mcpTool(TransitionProductTool::class, ['product' => 'TEE', 'transition' => 'approve'])->assertOk();
    mcpTool(TransitionProductTool::class, ['product' => 'TEE', 'transition' => 'publish'])->assertOk()->assertSee('"published_version":4');

    mcpTool(ListProductVersionsTool::class, ['product' => 'TEE'])->assertOk()->assertSee('"action":"published"');
    mcpTool(ShowProductVersionTool::class, ['product' => 'TEE', 'version' => 'published'])->assertOk()->assertSee('"snapshot"');

    mcpTool(UpdateProductTool::class, ['product' => 'TEE', 'enabled' => false])->assertOk()->assertSee('"status":"draft"');
    mcpTool(RevertProductTool::class, ['product' => 'TEE', 'version' => 1])->assertOk()->assertSee('"enabled":true');
});

it('lists an empty catalog', function (): void {
    mcpTool(ListAttributesTool::class)->assertOk()->assertSee('"data":[]');
});

it('shares one implementation with the HTTP API', function (): void {
    $this->postJson('/showroom/attributes', ['code' => 'color', 'type' => 'select'])->assertCreated();

    // The same Action refuses the duplicate on both surfaces.
    mcpTool(CreateAttributeTool::class, ['code' => 'color', 'type' => 'select'])->assertHasErrors();

    mcpTool(CreateAttributeOptionTool::class, ['attribute' => 'color', 'code' => 'red'])->assertOk();

    $this->getJson('/showroom/attributes/color')->assertOk()->assertJsonPath('data.options.0.code', 'red');
});

it('validates tool input with the same rules as the API', function (): void {
    mcpTool(CreateAttributeTool::class, ['code' => 'Bad Code', 'type' => 'text'])->assertHasErrors();
    mcpTool(CreateAttributeTool::class, ['code' => 'weight', 'type' => 'metric'])->assertHasErrors();
    mcpTool(UpdateAttributeTool::class, ['attribute' => 'weight', 'type' => 'text'])->assertHasErrors();
});

it('surfaces catalog rule messages an agent can act on', function (): void {
    $group = AttributeGroupModel::factory()->create(['code' => 'technical']);
    AttributeModel::factory()->for($group, 'group')->create();
    AttributeModel::factory()->create(['code' => 'name']);

    mcpTool(DeleteAttributeGroupTool::class, ['group' => 'technical'])
        ->assertHasErrors(['Attribute group "technical" still holds 1 attribute(s). Move them to another group first.']);

    mcpTool(CreateAttributeOptionTool::class, ['attribute' => 'name', 'code' => 'x'])
        ->assertHasErrors(['Attribute "name" is of type "text", which takes no options. Only select and multiselect attributes have options.']);
});

it('reports unknown records as not found', function (): void {
    mcpTool(ShowAttributeTool::class, ['attribute' => 'missing'])->assertHasErrors(['Not found.']);
});
