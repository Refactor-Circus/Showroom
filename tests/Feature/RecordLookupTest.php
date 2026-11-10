<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\DeleteOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\ShowOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\UpdateOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Actions\CreateProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\DeleteProductTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ShowProductTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\UpdateProductTool;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools\ListProductVersionsTool;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools\RevertProductTool;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools\ShowProductVersionTool;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools\TransitionProductTool;
use RefactorCircus\Showroom\ShowroomServiceProvider;
use RefactorCircus\Showroom\Support\LookupKey;
use RefactorCircus\Showroom\Support\RecordLookup;
use Symfony\Component\HttpFoundation\Response;

/*
 * Products and owners are found by their ULID `id` by default, or by their
 * `slug` (showroom.lookup.*), on every route, MCP tool and action that takes
 * one record. A call's `by` picks the key for itself. An identifier or owner
 * code is never a lookup key (D-001, D-010).
 */

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');
});

/**
 * Every route that takes a `{product}` or `{owner}`, with the method and a
 * body that lets the call succeed once the record is bound.
 *
 * @return array<string, array{0: string, 1: string, 2: array<string, mixed>, 3: array<string, mixed>}>
 */
function lookupRoutes(): array
{
    return [
        'showroom.products.show' => ['GET', 'product', [], []],
        'showroom.products.update' => ['PATCH', 'product', [], ['enabled' => false]],
        'showroom.products.destroy' => ['DELETE', 'product', [], []],
        'showroom.products.transitions.store' => ['POST', 'product', [], ['transition' => 'submit']],
        'showroom.products.versions.index' => ['GET', 'product', [], []],
        'showroom.products.versions.show' => ['GET', 'product', ['version' => 'latest'], []],
        'showroom.products.revert' => ['POST', 'product', [], ['version' => 1]],
        'atrium.showroom.products.show' => ['GET', 'product', [], []],
        'atrium.showroom.products.update' => ['PATCH', 'product', [], ['enabled' => '0']],
        'atrium.showroom.products.destroy' => ['DELETE', 'product', [], []],
        'atrium.showroom.products.transition' => ['POST', 'product', [], ['transition' => 'submit']],
        'atrium.showroom.products.revert' => ['POST', 'product', [], ['version' => 1]],
        'showroom.owners.show' => ['GET', 'owner', [], []],
        'showroom.owners.update' => ['PATCH', 'owner', [], ['labels' => ['en' => 'Acme EU']]],
        'showroom.owners.destroy' => ['DELETE', 'owner', [], []],
        'atrium.showroom.owners.show' => ['GET', 'owner', [], []],
        'atrium.showroom.owners.update' => ['PATCH', 'owner', [], []],
        'atrium.showroom.owners.destroy' => ['DELETE', 'owner', [], []],
    ];
}

/**
 * Each MCP tool that takes one product or owner, with the arguments a
 * successful call needs.
 *
 * @return array<string, array{0: class-string<Tool>, 1: string, 2: array<string, mixed>}>
 */
function lookupTools(): array
{
    return [
        'show product' => [ShowProductTool::class, 'product', []],
        'update product' => [UpdateProductTool::class, 'product', ['enabled' => false]],
        'delete product' => [DeleteProductTool::class, 'product', []],
        'transition product' => [TransitionProductTool::class, 'product', ['transition' => 'submit']],
        'list product versions' => [ListProductVersionsTool::class, 'product', []],
        'show product version' => [ShowProductVersionTool::class, 'product', ['version' => 'latest']],
        'revert product' => [RevertProductTool::class, 'product', ['version' => 1]],
        'show owner' => [ShowOwnerTool::class, 'owner', []],
        'update owner' => [UpdateOwnerTool::class, 'owner', ['labels' => ['en' => 'Acme EU']]],
        'delete owner' => [DeleteOwnerTool::class, 'owner', []],
    ];
}

/**
 * A product whose identifier, slug and id all differ, not only by case.
 */
function lookupProduct(): ProductModel
{
    return app(CreateProductAction::class)->execute(new CreateProductData(identifier: 'TEE_1.RED'));
}

/**
 * An owner whose code, slug and id all differ, not only by case.
 */
function lookupOwner(): OwnerModel
{
    if (! OwnerTypeModel::query()->where('code', 'vendor')->exists()) {
        OwnerTypeModel::factory()->create(['code' => 'vendor']);
    }

    return app(CreateOwnerAction::class)->execute(new CreateOwnerData(code: 'acme_eu', type: 'vendor', labels: ['en' => 'Acme Europe']));
}

/**
 * The record a route's parameter names, with its value under each key.
 *
 * @return array{0: ProductModel|OwnerModel, 1: array<string, string>}
 */
function lookupRecord(string $parameter): array
{
    $record = $parameter === 'product' ? lookupProduct() : lookupOwner();

    return [$record, [
        'id' => (string) $record->getKey(),
        'slug' => (string) $record->slug,
        // The identifier or code: never a lookup key.
        'reference' => $parameter === 'product' ? (string) $record->getAttribute('identifier') : (string) $record->getAttribute('code'),
    ]];
}

/**
 * @param  array<string, mixed>  $parameters
 * @param  array<string, mixed>  $body
 * @return TestResponse<Response>
 */
function callLookupRoute(string $route, string $method, string $parameter, string $value, array $parameters, array $body): TestResponse
{
    return test()->json($method, route($route, [$parameter => $value, ...$parameters]), $body);
}

/**
 * The route's call reached its controller with the record bound.
 */
function expectBound(TestResponse $response, string $parameter, Model $record): void
{
    expect($response->status())->not->toBe(404, (string) $response->getContent())
        ->and($response->status())->not->toBe(422, (string) $response->getContent());

    $bound = Route::current()?->parameter($parameter);

    expect($bound)->toBeInstanceOf($record::class)
        ->and($bound?->getKey())->toBe($record->getKey());
}

it('lists every route that takes a product or an owner', function (): void {
    // Only Showroom's own routes and Atrium screens: other packages, such as
    // Impex's `runs/{run}/owners/{owner}`, define their own `{owner}`.
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (Illuminate\Routing\Route $route): bool => str_starts_with((string) $route->getName(), 'showroom.') || str_starts_with((string) $route->getName(), 'atrium.showroom.'))
        ->filter(fn (Illuminate\Routing\Route $route): bool => array_intersect($route->parameterNames(), ['product', 'owner']) !== [])
        ->map(fn (Illuminate\Routing\Route $route): string => (string) $route->getName())
        ->sort()
        ->values()
        ->all();

    expect($routes)->toBe(collect(array_keys(lookupRoutes()))->sort()->values()->all());
});

describe('by the configured key', function (): void {
    it('binds the id by default; the slug and the identifier or code answer 404', function (string $route): void {
        [$method, $parameter, $parameters, $body] = lookupRoutes()[$route];
        [$record, $values] = lookupRecord($parameter);

        callLookupRoute($route, $method, $parameter, $values['slug'], $parameters, $body)->assertNotFound();
        callLookupRoute($route, $method, $parameter, $values['reference'], $parameters, $body)->assertNotFound();

        expectBound(callLookupRoute($route, $method, $parameter, $values['id'], $parameters, $body), $parameter, $record);
    })->with(array_keys(lookupRoutes()));

    it('binds the configured key; the others answer 404', function (string $route, string $key): void {
        [$method, $parameter, $parameters, $body] = lookupRoutes()[$route];
        config()->set('showroom.lookup.'.($parameter === 'product' ? 'products' : 'owners'), $key);
        [$record, $values] = lookupRecord($parameter);

        foreach (array_diff(array_keys($values), [$key]) as $other) {
            callLookupRoute($route, $method, $parameter, $values[$other], $parameters, $body)->assertNotFound();
        }

        expectBound(callLookupRoute($route, $method, $parameter, $values[$key], $parameters, $body), $parameter, $record);
    })->with(array_keys(lookupRoutes()))->with(['id', 'slug']);

    it('fetches a product whose identifier holds a slash and spaces by its id and by its slug', function (): void {
        $id = $this->postJson(route('showroom.products.store'), ['identifier' => '83AL-IC/40 4 RED ALUM'])->assertCreated()->json('data.id');

        $this->getJson(route('showroom.products.show', $id))
            ->assertOk()
            ->assertJsonPath('data.identifier', '83AL-IC/40 4 RED ALUM')
            ->assertJsonPath('data.slug', '83al-ic-40-4-red-alum');

        $this->getJson(route('showroom.products.show', ['product' => '83al-ic-40-4-red-alum', 'by' => 'slug']))
            ->assertOk()
            ->assertJsonPath('data.id', $id);
    });

    it('links the dashboard by the configured key', function (string $key): void {
        config()->set('showroom.lookup.products', $key);
        config()->set('showroom.lookup.owners', $key);
        $product = lookupProduct();
        $owner = lookupOwner();

        expect(route('atrium.showroom.products.show', $product))->toEndWith('/showroom/products/'.$product->getAttribute($key))
            ->and(route('showroom.owners.show', $owner))->toEndWith('/showroom/owners/'.$owner->getAttribute($key));

        $this->post(route('atrium.showroom.products.store'), ['identifier' => 'CAP'])
            ->assertRedirect(route('atrium.showroom.products.show', ProductModel::query()->where('identifier', 'CAP')->value($key)));
    })->with(['id', 'slug']);

    it('keeps flags bound by code', function (): void {
        $flag = FlagModel::factory()->create(['code' => 'pricing_only']);

        $this->getJson(route('showroom.flags.show', 'pricing_only'))->assertOk()->assertJsonPath('data.code', 'pricing_only');
        $this->getJson(route('showroom.flags.show', $flag->getKey()))->assertNotFound();
    });

    it('binds host routes that share a parameter name as Laravel would', function (): void {
        Route::get('host/products/{product}', fn (ProductModel $product): string => (string) $product->getAttribute('identifier'))->middleware('web');
        config()->set('showroom.lookup.products', 'slug');
        $product = lookupProduct();

        // Implicit binding by the model's route key, which follows the config.
        $this->get('/host/products/'.$product->slug)->assertOk()->assertSee('TEE_1.RED');
        $this->get('/host/products/'.$product->slug.'?by=bogus')->assertOk();
    });
});

describe('the by override', function (): void {
    it('binds the key a call asks for over HTTP, whatever the config says', function (string $route, string $configured): void {
        [$method, $parameter, $parameters, $body] = lookupRoutes()[$route];
        config()->set('showroom.lookup.'.($parameter === 'product' ? 'products' : 'owners'), $configured);
        $by = $configured === 'id' ? 'slug' : 'id';
        [$record, $values] = lookupRecord($parameter);

        callLookupRoute($route, $method, $parameter, $values[$configured], [...$parameters, 'by' => $by], $body)->assertNotFound();

        expectBound(callLookupRoute($route, $method, $parameter, $values[$by], [...$parameters, 'by' => $by], $body), $parameter, $record);
    })->with(array_keys(lookupRoutes()))->with(['id', 'slug']);

    it('answers 422 at by for a key that is not a lookup key, before the action runs', function (string $route, string $by): void {
        [$method, $parameter, $parameters, $body] = lookupRoutes()[$route];
        [$record, $values] = lookupRecord($parameter);

        callLookupRoute($route, $method, $parameter, $by === 'code' || $by === 'identifier' ? $values['reference'] : $values['id'], [...$parameters, 'by' => $by], $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['by']);

        expect($record->newQuery()->whereKey($record->getKey())->exists())->toBeTrue();
    })->with(array_keys(lookupRoutes()))->with(['identifier', 'code', 'bogus']);
});

describe('MCP tools', function (): void {
    it('publishes an optional by argument limited to the lookup keys', function (string $tool): void {
        [$class, $parameter] = lookupTools()[$tool];
        $input = app($class)->toArray()['inputSchema'];

        expect($input['properties']['by']['enum'] ?? null)->toBe(['id', 'slug'])
            ->and($input['required'] ?? [])->not->toContain('by')
            ->and($input['properties'][$parameter]['description'] ?? '')->toContain('id');
    })->with(array_keys(lookupTools()));

    it('finds the record by its id by default; the slug and the identifier or code are not found', function (string $tool): void {
        [$class, $parameter, $arguments] = lookupTools()[$tool];
        [, $values] = lookupRecord($parameter);

        mcpTool($class, [$parameter => $values['slug'], ...$arguments])->assertHasErrors(['Not found.']);
        mcpTool($class, [$parameter => $values['reference'], ...$arguments])->assertHasErrors(['Not found.']);
        mcpTool($class, [$parameter => $values['id'], ...$arguments])->assertOk()->assertHasNoErrors();
    })->with(array_keys(lookupTools()));

    it('finds the record by the configured slug', function (string $tool): void {
        [$class, $parameter, $arguments] = lookupTools()[$tool];
        config()->set('showroom.lookup.'.($parameter === 'product' ? 'products' : 'owners'), 'slug');
        [, $values] = lookupRecord($parameter);

        mcpTool($class, [$parameter => $values['id'], ...$arguments])->assertHasErrors(['Not found.']);
        mcpTool($class, [$parameter => $values['slug'], ...$arguments])->assertOk()->assertHasNoErrors();
    })->with(array_keys(lookupTools()));

    it('finds the record by the key a call asks for with by', function (string $tool, string $configured): void {
        [$class, $parameter, $arguments] = lookupTools()[$tool];
        config()->set('showroom.lookup.'.($parameter === 'product' ? 'products' : 'owners'), $configured);
        $by = $configured === 'id' ? 'slug' : 'id';
        [, $values] = lookupRecord($parameter);

        mcpTool($class, [$parameter => $values[$configured], 'by' => $by, ...$arguments])->assertHasErrors(['Not found.']);
        mcpTool($class, [$parameter => $values[$by], 'by' => $by, ...$arguments])->assertOk()->assertHasNoErrors();
    })->with(array_keys(lookupTools()))->with(['id', 'slug']);

    it('refuses a by that is not a lookup key as a validation error, before the action runs', function (string $tool, string $by): void {
        [$class, $parameter, $arguments] = lookupTools()[$tool];
        [$record, $values] = lookupRecord($parameter);

        mcpTool($class, [$parameter => $values['reference'], 'by' => $by, ...$arguments])->assertHasErrors(['The selected by is invalid.']);

        expect($record->newQuery()->whereKey($record->getKey())->exists())->toBeTrue();
    })->with(array_keys(lookupTools()))->with(['identifier', 'code', 'bogus']);
});

describe('the resolver', function (): void {
    it('resolves by the configured key, or by the key a call names', function (): void {
        $product = lookupProduct();
        $owner = lookupOwner();
        $lookup = app(RecordLookup::class);

        expect($lookup->resolve('products', $product->id)->is($product))->toBeTrue()
            ->and($lookup->resolve('products', 'tee-1-red', LookupKey::Slug)->is($product))->toBeTrue()
            ->and($lookup->owner('acme-europe', LookupKey::Slug)->is($owner))->toBeTrue()
            ->and(fn () => $lookup->product('TEE_1.RED'))->toThrow(ModelNotFoundException::class)
            ->and(fn () => $lookup->product('TEE_1.RED', LookupKey::Slug))->toThrow(ModelNotFoundException::class);

        config()->set('showroom.lookup.owners', 'slug');

        expect($lookup->key('owners'))->toBe(LookupKey::Slug)
            ->and($lookup->resolve('owners', 'acme-europe')->is($owner))->toBeTrue()
            ->and(fn () => $lookup->resolve('flags', 'pricing_only'))->toThrow(InvalidArgumentException::class);
    });

    it('defaults to the id when a published config has no lookup section', function (): void {
        config()->set('showroom.lookup', null);

        expect(app(RecordLookup::class)->key('products'))->toBe(LookupKey::Id)
            ->and(app(RecordLookup::class)->key('owners'))->toBe(LookupKey::Id);
    });
});

describe('the boot check', function (): void {
    it('throws naming the key when a configured lookup key is not id or slug', function (string $subject, mixed $value): void {
        config()->set('showroom.lookup.'.$subject, $value);

        expect(fn () => (new ShowroomServiceProvider(app()))->boot())
            ->toThrow(InvalidArgumentException::class, 'showroom.lookup.'.$subject);
    })->with([
        'bogus products' => ['products', 'bogus'],
        'bogus owners' => ['owners', 'bogus'],
        'identifier' => ['products', 'identifier'],
        'code' => ['owners', 'code'],
        'not a string' => ['products', ['id']],
    ]);
});
