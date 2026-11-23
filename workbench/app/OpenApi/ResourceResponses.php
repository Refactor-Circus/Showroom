<?php

declare(strict_types=1);

namespace Workbench\App\OpenApi;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\Type as OpenApiType;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\ObjectType;
use RefactorCircus\Showroom\Domains\Catalog\Resources\CatalogSearchResultResource;
use RefactorCircus\Showroom\Domains\Catalog\Resources\CatalogStatsResource;
use RefactorCircus\Showroom\Domains\Catalog\Resources\SettingsSummaryResource;

/**
 * Writes the success response of the operations whose controllers answer
 * through `$request->persist()`, where Scramble cannot follow the response
 * to its resource, from the resource's own `schema()`: the shape the API
 * and the MCP tools promise.
 */
final class ResourceResponses extends OperationExtension
{
    /**
     * The success response body of each operation, keyed by route name.
     *
     * @return array<string, ObjectType>
     */
    public static function responses(JsonSchema $schema): array
    {
        return [
            'showroom.stats.show' => $schema->object(CatalogStatsResource::structuredSchema($schema)),
            'showroom.search.index' => $schema->object([
                'data' => $schema->array()->items($schema->object(CatalogSearchResultResource::schema($schema)))->required(),
            ]),
            'showroom.settings-summary.show' => $schema->object(SettingsSummaryResource::structuredSchema($schema)),
        ];
    }

    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $body = self::responses(new JsonSchemaTypeFactory)[(string) $routeInfo->route->getName()] ?? null;

        if ($body === null) {
            return;
        }

        $description = '';
        $others = [];

        foreach ($operation->responses ?? [] as $response) {
            if ($response instanceof Response && self::isSuccess($response)) {
                $description = $description !== '' ? $description : (string) $response->description;
            } else {
                $others[] = $response;
            }
        }

        $operation->responses = [
            Response::make(200)
                ->setDescription($description)
                ->setContent('application/json', Schema::fromType(self::raw($body->toArray()))),
            ...$others,
        ];
    }

    private static function isSuccess(Response $response): bool
    {
        return is_numeric($response->code) && (int) $response->code >= 200 && (int) $response->code < 300;
    }

    /**
     * A JSON Schema written as it is: OpenAPI 3.1 schemas are JSON Schema.
     *
     * @param  array<string, mixed>  $schema
     */
    private static function raw(array $schema): OpenApiType
    {
        return new class($schema) extends OpenApiType
        {
            /**
             * @param  array<string, mixed>  $schema
             */
            public function __construct(private readonly array $schema)
            {
                parent::__construct('object');
            }

            public function serializeAs31(): mixed
            {
                return $this->schema;
            }

            public function serializeAs32(): mixed
            {
                return $this->schema;
            }
        };
    }
}
