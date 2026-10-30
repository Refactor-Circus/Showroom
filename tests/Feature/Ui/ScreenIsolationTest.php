<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Atrium\Testing\ResetsScreenExtensions;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;
use RefactorCircus\Showroom\Tests\TestCase;

/*
 * What one test registers on a screen never reaches the next. These two run
 * in order: the first registers on showroom.product, the second finds
 * nothing there.
 */

beforeEach(function (): void {
    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');

    Catalog::apparel();

    ProductModel::query()->create(['identifier' => 'TEE-1']);

    $directory = sys_get_temp_dir().'/showroom-screen-isolation-'.getmypid();
    File::ensureDirectoryExists($directory);
    File::put($directory.'/marker.blade.php', '<span>isolated</span>');
    View::addNamespace('screen-isolation', $directory);
});

it('registers a tab and a section on the product screen', function (): void {
    Atrium::screens()
        ->tab('showroom.product', TabDefinition::make('acme.isolated')->view('screen-isolation::marker')->public())
        ->section('showroom.product', 'before', SectionDefinition::make('acme.isolated')->view('screen-isolation::marker')->public());

    $this->get(route('atrium.showroom.products.show', 'TEE-1'))
        ->assertOk()
        ->assertSee('data-testid="tab-acme.isolated"', false)
        ->assertSee('data-testid="section-acme.isolated"', false);
});

it('starts the next test without them', function (): void {
    expect(class_uses_recursive(TestCase::class))->toContain(ResetsScreenExtensions::class)
        ->and(Atrium::screens()->tabs('showroom.product'))->toBe([])
        ->and(Atrium::screens()->sections('showroom.product', 'before'))->toBe([]);

    $this->get(route('atrium.showroom.products.show', 'TEE-1'))
        ->assertOk()
        ->assertDontSee('data-testid="tab-acme.isolated"', false)
        ->assertDontSee('data-testid="section-acme.isolated"', false);
});
