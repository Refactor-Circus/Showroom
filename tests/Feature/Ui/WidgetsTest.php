<?php

declare(strict_types=1);

use RefactorCircus\Atrium\Domains\Widgets\Data\WidgetDefinition;
use RefactorCircus\Atrium\Domains\Widgets\Services\WidgetRegistry;
use RefactorCircus\Showroom\Atrium\ShowroomPlugin;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

function renderWidget(string $key): string
{
    $definition = app(WidgetRegistry::class)->get($key);

    expect($definition)->toBeInstanceOf(WidgetDefinition::class);

    /** @var WidgetDefinition $definition */
    /** @var view-string $view */
    $view = $definition->view;

    return view($view, $definition->resolveData())->render();
}

it('offers widgets without placing any', function (): void {
    $keys = array_map(
        fn (WidgetDefinition $definition): string => $definition->key,
        app(ShowroomPlugin::class)->widgets(),
    );

    expect($keys)->toBe(['showroom.product-status', 'showroom.completeness', 'showroom.review-queue', 'showroom.recent-changes'])
        ->and(app(WidgetRegistry::class)->all())->toHaveKeys($keys);
});

it('renders empty widgets on a new catalog', function (): void {
    expect(renderWidget('showroom.completeness'))->toContain(__('showroom::showroom.no_completeness_yet'))
        ->and(renderWidget('showroom.review-queue'))->toContain(__('showroom::showroom.nothing_in_review'))
        ->and(renderWidget('showroom.recent-changes'))->toContain(__('showroom::showroom.no_changes'));
});

it('summarises the catalog', function (): void {
    Catalog::apparel();

    $attributes = ['name', 'description', 'ean', 'weight', 'price', 'pack_size', 'rating', 'organic', 'released', 'color', 'size', 'tags'];

    $this->patchJson('/showroom/families/shirts', ['attributes' => array_map(
        fn (string $code): array => ['attribute' => $code, 'is_required' => $code === 'name'],
        $attributes,
    )])->assertOk();

    $ids = [];

    foreach (['TEE-1' => 'Tee', 'TEE-2' => null] as $identifier => $name) {
        $ids[$identifier] = $this->postJson('/showroom/products', [
            'identifier' => $identifier,
            'family' => 'shirts',
            'values' => $name === null ? [] : ['name' => Catalog::value($name)],
        ])->assertCreated()->json('data.id');
    }

    $this->postJson('/showroom/products/'.$ids['TEE-1'].'/transitions', ['transition' => 'submit'])->assertOk();

    $status = renderWidget('showroom.product-status');

    expect($status)->toMatch('/data-status="draft".*?>\s*1\s*</s')
        ->and($status)->toMatch('/data-status="in_review".*?>\s*1\s*</s')
        ->and($status)->toMatch('/data-status="published".*?>\s*0\s*</s');

    // TEE-1 has every required value, TEE-2 none: 1 of 2 complete.
    expect(renderWidget('showroom.completeness'))
        ->toContain('data-slot="ecommerce-en"')
        ->toContain('50%')
        ->toContain('1 of 2 complete');

    expect(renderWidget('showroom.review-queue'))
        ->toContain('data-product="TEE-1"')
        ->not->toContain('data-product="TEE-2"');

    expect(renderWidget('showroom.recent-changes'))
        ->toContain('TEE-1')
        ->toContain('submitted');
});
