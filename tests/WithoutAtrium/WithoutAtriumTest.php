<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Testing\TestListResponse;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Keystone\Testing\McpTools;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ListProductsTool;
use RefactorCircus\Showroom\Domains\Transfer\Mcp\Tools\StartExportTool;
use RefactorCircus\Showroom\Domains\Transfer\Mcp\Tools\StartImportTool;
use RefactorCircus\Showroom\Impex\ImpexIntegration;
use RefactorCircus\Showroom\Mcp\ShowroomServer;
use RefactorCircus\Showroom\ShowroomServiceProvider;
use RefactorCircus\Showroom\Tests\WithoutAtrium\WithoutAtriumTestCase;

uses(WithoutAtriumTestCase::class);

/**
 * The Atrium classes the test harness loaded before tests/WithoutAtrium/bootstrap.php
 * ran: tests/Pest.php's uses() loads the main suite's TestCase and its
 * ResetsScreenExtensions.
 *
 * @return array<int, string>
 */
function preloadedAtriumClasses(): array
{
    /** @var array<int, string> $preloaded */
    $preloaded = $GLOBALS['showroom_atrium_preloaded'] ?? [];

    return $preloaded;
}

/**
 * Every Atrium class, interface and trait PHP has loaded since the bootstrap.
 *
 * @return array<int, string>
 */
function loadedAtriumClasses(): array
{
    return array_values(array_diff(
        array_filter(
            [...get_declared_classes(), ...get_declared_interfaces(), ...get_declared_traits()],
            fn (string $class): bool => str_starts_with($class, 'RefactorCircus\\Atrium\\'),
        ),
        preloadedAtriumClasses(),
    ));
}

/**
 * The names of the catalog tools a tool search for the query finds.
 *
 * @return array<int, string>
 */
function searchedShowroomTools(string $query): array
{
    $search = (new ShowroomServer(new FakeTransporter))->createContext()->tools()
        ->firstOrFail(fn (Tool $tool): bool => $tool->name() === 'search_tools');

    /** @var array{ok: bool, tools: array<int, array{name: string}>} $payload */
    $payload = json_decode((string) $search->handle(new Request(['query' => $query, 'limit' => 50]))->content(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['ok'])->toBeTrue();

    return array_column($payload['tools'], 'name');
}

it('cannot load any Atrium class', function (): void {
    expect(class_exists('RefactorCircus\\Atrium\\AtriumServiceProvider'))->toBeFalse()
        ->and(class_exists('RefactorCircus\\Atrium\\Atrium'))->toBeFalse()
        ->and(loadedAtriumClasses())->toBeEmpty()
        // Only the harness's testing helpers were loaded before the bootstrap.
        ->and(array_filter(
            preloadedAtriumClasses(),
            fn (string $class): bool => ! str_starts_with($class, 'RefactorCircus\\Atrium\\Testing\\'),
        ))->toBe([]);
});

it('boots the provider without Atrium', function (): void {
    expect($this->app->isBooted())->toBeTrue()
        ->and($this->app->getProvider(ShowroomServiceProvider::class))->not->toBeNull()
        ->and(app(PackageRegistry::class)->get('showroom'))->not->toBeNull()
        ->and(app(ImpexIntegration::class)->active())->toBeFalse();
});

it('registers no Atrium route, and no transfer route without Impex', function (): void {
    Route::getRoutes()->refreshNameLookups();

    $atrium = array_filter(
        Route::getRoutes()->getRoutes(),
        fn (RoutingRoute $route): bool => str_starts_with((string) $route->getName(), 'atrium.') || str_starts_with($route->uri(), 'atrium'),
    );

    expect($atrium)->toBeEmpty()
        ->and(Route::has('showroom.products.index'))->toBeTrue()
        ->and(Route::has('showroom.imports.store'))->toBeFalse()
        ->and(Route::has('showroom.exports.store'))->toBeFalse();
});

it('serves the JSON API', function (): void {
    $this->postJson(route('showroom.products.store'), ['identifier' => 'TEE'])
        ->assertCreated()
        ->assertJsonPath('data.identifier', 'TEE');

    $this->getJson(route('showroom.products.index'))
        ->assertOk()
        ->assertJsonPath('data.0.identifier', 'TEE');

    expect(loadedAtriumClasses())->toBeEmpty();
});

it('answers MCP tools/list and the catalog tools', function (): void {
    expect(ShowroomServer::tools())->toBeInstanceOf(TestListResponse::class);

    $this->postJson(route('showroom.products.store'), ['identifier' => 'TEE'])->assertCreated();

    McpTools::call(ShowroomServer::class, ListProductsTool::class)
        ->assertOk()
        ->assertSee('TEE');

    expect(loadedAtriumClasses())->toBeEmpty();
});

it('offers no transfer tool without Impex', function (): void {
    $transfers = searchedShowroomTools('start import export run');

    expect(searchedShowroomTools('list products'))->toContain('list-products-tool')
        ->and($transfers)->not->toContain('start-import-tool')
        ->and($transfers)->not->toContain('start-export-tool');

    McpTools::call(ShowroomServer::class, StartImportTool::class)->assertHasErrors();
    McpTools::call(ShowroomServer::class, StartExportTool::class)->assertHasErrors();
});
