<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Atrium\Domains\Plugins\Services\PluginRegistry;
use RefactorCircus\Atrium\Domains\Search\Data\SearchResult;
use RefactorCircus\Showroom\Atrium\Features\ShowroomSupportFeature;
use RefactorCircus\Showroom\Atrium\ShowroomPlugin;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Tests\Fixtures\OrphanSupportFeature;

it('registers itself with atrium', function (): void {
    expect(app(PluginRegistry::class)->has('showroom'))->toBeTrue();
});

it('contributes catalog navigation', function (): void {
    $labels = array_map(
        fn (NavItem $item): string => $item->label,
        app(ShowroomPlugin::class)->navigation(),
    );

    expect($labels)->toBe(['Products', 'Product models', 'Categories', 'Assets', 'Owners', 'Families', 'Attributes', 'Channels', 'Import & export', 'Association types', 'Attribute groups']);
});

it('registers its routes inside the atrium group', function (): void {
    expect(Route::has('atrium.showroom.attributes.index'))->toBeTrue()
        ->and(Route::has('atrium.showroom.attribute-groups.index'))->toBeTrue()
        ->and(route('atrium.showroom.attributes.index'))->toContain('/atrium/showroom/attributes');
});

it('offers a settings panel', function (): void {
    expect(app(ShowroomPlugin::class)->settings()?->key)->toBe('showroom');
});

it('finds attributes and groups by code or label', function (): void {
    AttributeModel::factory()->create(['code' => 'color', 'labels' => ['en' => 'Colour']]);
    AttributeGroupModel::factory()->create(['code' => 'colors_and_finishes', 'labels' => ['en' => 'Colors and finishes']]);
    AttributeModel::factory()->create(['code' => 'weight']);

    $source = app(ShowroomPlugin::class)->search();

    $titles = array_map(
        fn (SearchResult $result): string => $result->title,
        $source?->results('colo') ?? [],
    );

    expect($titles)->toBe(['Colour', 'Colors and finishes']);
});

it('skips feature classes that are not installed', function (): void {
    config()->set('showroom.atrium.features', [
        'RefactorCircus\\Showroom\\Tests\\Fixtures\\NoSuchFeature',
        // Its parent class is missing, as ShowroomSupportFeature's is without
        // refactor-circus/pennantplus, so loading it throws rather than answering false.
        OrphanSupportFeature::class,
        'catalog-enabled',
        ShowroomSupportFeature::class,
    ]);

    expect(app(ShowroomPlugin::class)->features())->toBe(['catalog-enabled', ShowroomSupportFeature::class]);
});

it('checks no feature when none are configured', function (): void {
    config()->set('showroom.atrium.features', []);

    expect(app(ShowroomPlugin::class)->features())->toBe([]);
});
