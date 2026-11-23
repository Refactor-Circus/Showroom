<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use RefactorCircus\Showroom\Domains\Catalog\Resources\CatalogSearchResultResource;
use RefactorCircus\Showroom\Domains\Catalog\Resources\CatalogStatsResource;
use RefactorCircus\Showroom\Domains\Catalog\Resources\SettingsSummaryResource;

/*
 * openapi.json and client/ are generated (`composer client:generate`) and
 * committed. These checks fail when either is stale, without Node (D-008):
 * the document is exported again in-process, and the client is tied to it
 * by the sha256 stamp on the first line of its schema.
 */

function showroomPackagePath(string $path): string
{
    return dirname(__DIR__, 2).'/'.$path;
}

function showroomCommittedOpenApi(): string
{
    return (string) file_get_contents(showroomPackagePath('openapi.json'));
}

/**
 * @return array<string, mixed>
 */
function showroomOpenApiDocument(): array
{
    /** @var array<string, mixed> $document */
    $document = json_decode(showroomCommittedOpenApi(), true, flags: JSON_THROW_ON_ERROR);

    return $document;
}

/**
 * The operation of a route, found by its `operationId` (the route name).
 *
 * @return array<string, mixed>
 */
function showroomOpenApiOperation(string $routeName): array
{
    /** @var array<string, array<string, mixed>> $paths */
    $paths = showroomOpenApiDocument()['paths'];

    foreach ($paths as $operations) {
        foreach ($operations as $operation) {
            if (is_array($operation) && ($operation['operationId'] ?? null) === $routeName) {
                return $operation;
            }
        }
    }

    throw new RuntimeException("openapi.json has no operation {$routeName}.");
}

/**
 * A schema with its local `$ref`s followed.
 *
 * @param  array<string, mixed>  $schema
 * @return array<string, mixed>
 */
function showroomOpenApiResolve(array $schema): array
{
    while (isset($schema['$ref']) && is_string($schema['$ref'])) {
        $target = showroomOpenApiDocument();

        foreach (explode('/', substr($schema['$ref'], 2)) as $segment) {
            /** @var array<string, mixed> $target */
            $target = $target[$segment];
        }

        $schema = $target;
    }

    return $schema;
}

/**
 * The object schema under `data` in a route's 200 response: the object
 * itself, or each item of a list.
 *
 * @return array<string, mixed>
 */
function showroomOpenApiData(string $routeName): array
{
    /** @var array<string, mixed> $responses */
    $responses = showroomOpenApiOperation($routeName)['responses'];

    /** @var array<string, mixed> $response */
    $response = showroomOpenApiResolve((array) ($responses['200'] ?? []));
    /** @var array<string, mixed> $body */
    $body = showroomOpenApiResolve((array) ($response['content']['application/json']['schema'] ?? []));

    expect($body['type'] ?? null)->toBe('object')
        ->and($body['required'] ?? [])->toContain('data');

    /** @var array<string, mixed> $data */
    $data = showroomOpenApiResolve((array) ($body['properties']['data'] ?? []));

    if (($data['type'] ?? null) === 'array') {
        /** @var array<string, mixed> $data */
        $data = showroomOpenApiResolve((array) ($data['items'] ?? []));
    }

    return $data;
}

/**
 * The keys a resource's `schema()` marks required.
 *
 * @param  Closure(JsonSchemaTypeFactory): array<string, mixed>  $schema
 * @return list<string>
 */
function showroomRequiredKeys(Closure $schema): array
{
    /** @var list<string> $required */
    $required = JsonSchema::object($schema)->toArray()['required'] ?? [];

    return $required;
}

/**
 * Every `METHOD /path` of the package's default API routes, as the document
 * writes them: relative to the `/showroom` server, binding fields dropped.
 *
 * @return list<string>
 */
function showroomApiRoutes(): array
{
    $prefix = config('showroom.routes.prefix').'/';
    $operations = [];

    foreach (app('router')->getRoutes() as $route) {
        /** @var Route $route */
        $name = (string) $route->getName();

        if (! str_starts_with($name, 'showroom.') || ! str_starts_with($route->uri(), $prefix)) {
            continue;
        }

        $path = '/'.preg_replace('/\{(\w+)(?::\w+)?\??\}/', '{$1}', substr($route->uri(), strlen($prefix)));

        foreach (array_diff($route->methods(), ['HEAD']) as $method) {
            $operations[] = $method.' '.$path;
        }
    }

    sort($operations);

    return $operations;
}

/**
 * @return list<string>
 */
function showroomDocumentedOperations(): array
{
    $operations = [];

    /** @var array<string, array<string, mixed>> $paths */
    $paths = showroomOpenApiDocument()['paths'];

    foreach ($paths as $path => $item) {
        foreach (array_keys($item) as $method) {
            if (in_array($method, ['get', 'put', 'post', 'patch', 'delete'], true)) {
                $operations[] = strtoupper($method).' '.$path;
            }
        }
    }

    sort($operations);

    return $operations;
}

it('equals a fresh export from the workbench configuration', function (): void {
    $path = (string) tempnam(sys_get_temp_dir(), 'showroom-openapi-');

    try {
        expect(Artisan::call('scramble:export', ['--path' => $path]))->toBe(0);

        expect((string) file_get_contents($path))
            ->toBe(showroomCommittedOpenApi(), 'openapi.json is stale: run composer client:generate.');
    } finally {
        @unlink($path);
    }
});

it('is the Showroom 2.0.0 document served from a relative /showroom', function (): void {
    $document = showroomOpenApiDocument();

    expect($document['openapi'])->toStartWith('3.1.')
        ->and($document['info']['title'])->toBe('Showroom')
        ->and($document['info']['version'])->toBe('2.0.0')
        ->and($document['servers'])->toBe([['url' => '/showroom']]);
});

it('documents every default API route by method and path, and nothing else', function (): void {
    $routes = showroomApiRoutes();

    expect($routes)->toContain('GET /stats', 'GET /search', 'GET /settings-summary', 'GET /products/{product}')
        ->and(showroomDocumentedOperations())->toBe($routes);
});

it('names each operation after its route', function (): void {
    foreach (['showroom.stats.show', 'showroom.search.index', 'showroom.settings-summary.show', 'showroom.products.index'] as $name) {
        expect(showroomOpenApiOperation($name)['operationId'])->toBe($name);
    }
});

it('describes the new endpoints\' success responses with their resource schema', function (string $routeName, Closure $schema): void {
    $required = showroomRequiredKeys($schema);
    $data = showroomOpenApiData($routeName);

    expect($required)->not->toBe([])
        ->and($data['type'] ?? null)->toBe('object')
        ->and($data['required'] ?? [])->toContain(...$required)
        ->and(array_keys((array) ($data['properties'] ?? [])))->toContain(...$required);
})->with([
    'stats' => ['showroom.stats.show', fn (JsonSchemaTypeFactory $schema): array => CatalogStatsResource::schema($schema)],
    'search' => ['showroom.search.index', fn (JsonSchemaTypeFactory $schema): array => CatalogSearchResultResource::schema($schema)],
    'settings summary' => ['showroom.settings-summary.show', fn (JsonSchemaTypeFactory $schema): array => SettingsSummaryResource::schema($schema)],
]);

it('answers search with a list of results', function (): void {
    /** @var array<string, mixed> $response */
    $response = showroomOpenApiOperation('showroom.search.index')['responses']['200'];

    expect($response['content']['application/json']['schema']['properties']['data']['type'])->toBe('array');
});

it('lists the search query parameters', function (): void {
    /** @var list<array<string, mixed>> $parameters */
    $parameters = showroomOpenApiOperation('showroom.search.index')['parameters'] ?? [];
    $query = array_column(array_filter($parameters, fn (array $parameter): bool => $parameter['in'] === 'query'), null, 'name');

    expect(array_keys($query))->toContain('q', 'type', 'limit')
        ->and($query['q']['required'] ?? false)->toBeTrue();
});

it('stamps the client schema with the sha256 of the committed openapi.json', function (): void {
    $firstLine = strtok((string) file_get_contents(showroomPackagePath('client/src/schema.d.ts')), "\n");

    expect($firstLine)->toBe('// openapi.json sha256: '.hash_file('sha256', showroomPackagePath('openapi.json')), 'client/ is stale: run composer client:generate.');
});

it('ships the built schema unchanged', function (): void {
    expect(showroomPackagePath('client/dist/schema.d.ts'))->toBeFile()
        ->and((string) file_get_contents(showroomPackagePath('client/dist/schema.d.ts')))
        ->toBe((string) file_get_contents(showroomPackagePath('client/src/schema.d.ts')), 'client/dist is stale: run composer client:generate.');
});

it('builds the client entry point into dist', function (): void {
    expect(showroomPackagePath('client/dist/index.js'))->toBeFile()
        ->and(showroomPackagePath('client/dist/index.d.ts'))->toBeFile()
        ->and((string) file_get_contents(showroomPackagePath('client/src/index.ts')))
        ->toContain('export function createShowroomClient(')
        ->toContain('Client<paths>')
        ->toContain('paths')
        ->toContain('components')
        ->toContain('operations');
});

it('describes the client package', function (): void {
    /** @var array<string, mixed> $package */
    $package = json_decode((string) file_get_contents(showroomPackagePath('client/package.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($package['name'])->toBe('@refactor-circus/showroom-client')
        ->and($package['version'])->toBe('2.0.0')
        ->and($package['dependencies'])->toHaveKey('openapi-fetch')
        ->and($package['devDependencies'])->toHaveKeys(['openapi-typescript', 'typescript'])
        ->and($package['main'])->toBe('./dist/index.js')
        ->and($package['types'])->toBe('./dist/index.d.ts')
        ->and($package['exports']['.'])->toBe([
            'types' => './dist/index.d.ts',
            'import' => './dist/index.js',
            'default' => './dist/index.js',
        ])
        ->and($package['scripts']['generate'])
        ->toContain('openapi-typescript ../openapi.json -o src/schema.d.ts')
        ->toContain('node scripts/stamp.mjs')
        ->toContain('tsc');
});

it('keeps the client dependencies out of git and the Composer dist', function (): void {
    expect((string) file_get_contents(showroomPackagePath('.gitignore')))->toContain('client/node_modules')
        ->and((string) file_get_contents(showroomPackagePath('.gitattributes')))->toMatch('#^/client/node_modules\s+export-ignore$#m');
});
