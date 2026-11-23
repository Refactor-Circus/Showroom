<?php

declare(strict_types=1);

use RefactorCircus\Showroom\Domains\Catalog\Mcp\Tools\ShowCatalogStatsTool;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Mcp\ShowroomServer;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

it('is offered as show-catalog-stats-tool', function (): void {
    expect(ShowroomServer::TOOLS)->toContain(ShowCatalogStatsTool::class)
        ->and(app(ShowCatalogStatsTool::class)->name())->toBe('show-catalog-stats-tool');
});

it('answers the same figures as the API, as structured content', function (): void {
    Catalog::apparel();

    $this->postJson(route('showroom.products.store'), ['identifier' => 'TEE', 'family' => 'shirts', 'values' => ['name' => Catalog::value('Tee')]])->assertCreated();
    $this->postJson(route('showroom.products.store'), ['identifier' => 'POLO', 'family' => 'shirts'])->assertCreated();
    $this->postJson(route('showroom.products.transitions.store', ProductModel::query()->where('identifier', 'TEE')->value('id')), ['transition' => 'submit'])->assertOk();

    $api = $this->getJson(route('showroom.stats.show'))->assertOk()->json();

    expect($api['data']['review_queue']['total'])->toBe(1);

    mcpTool(ShowCatalogStatsTool::class)
        ->assertOk()
        ->assertStructuredContent($api);
});

it('refuses a guest while authorization is on', function (): void {
    config()->set('showroom.authorization', true);

    mcpTool(ShowCatalogStatsTool::class)->assertHasErrors(['Unauthorized.']);
});
