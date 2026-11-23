<?php

declare(strict_types=1);

use RefactorCircus\Showroom\Domains\Catalog\Mcp\Tools\SearchCatalogTool;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Mcp\ShowroomServer;

it('is offered as search-catalog-tool', function (): void {
    expect(ShowroomServer::TOOLS)->toContain(SearchCatalogTool::class)
        ->and(app(SearchCatalogTool::class)->name())->toBe('search-catalog-tool');
});

it('answers the same hits as the API, as structured content', function (): void {
    ProductModel::factory()->create(['identifier' => 'OAK-1']);
    CategoryModel::factory()->create(['code' => 'oak_furniture', 'labels' => ['en' => 'Oak furniture']]);
    OwnerModel::factory()->create(['code' => 'oakwood', 'labels' => ['en' => 'Oakwood'], 'owner_type_id' => OwnerTypeModel::factory()->create(['code' => 'vendor'])->id]);

    $api = $this->getJson(route('showroom.search.index', ['q' => 'oak']))->assertOk()->json();

    expect($api['data'])->toHaveCount(3);

    mcpTool(SearchCatalogTool::class, ['q' => 'oak'])
        ->assertOk()
        ->assertStructuredContent($api);

    $owners = $this->getJson(route('showroom.search.index', ['q' => 'oak', 'type' => 'owners', 'limit' => 1]))->assertOk()->json();

    mcpTool(SearchCatalogTool::class, ['q' => 'oak', 'type' => 'owners', 'limit' => 1])
        ->assertOk()
        ->assertStructuredContent($owners);
});

it('answers an empty list when nothing matches', function (): void {
    mcpTool(SearchCatalogTool::class, ['q' => 'nothing'])
        ->assertOk()
        ->assertStructuredContent(['data' => []]);
});

it('refuses a missing q and an unknown type', function (array $arguments): void {
    mcpTool(SearchCatalogTool::class, $arguments)->assertHasErrors();
})->with([
    'no q' => [[]],
    'an unknown type' => [['q' => 'oak', 'type' => 'transfers']],
    'a limit of 21' => [['q' => 'oak', 'limit' => 21]],
]);

it('refuses a guest while authorization is on', function (): void {
    config()->set('showroom.authorization', true);

    mcpTool(SearchCatalogTool::class, ['q' => 'oak'])->assertHasErrors(['Unauthorized.']);
});
