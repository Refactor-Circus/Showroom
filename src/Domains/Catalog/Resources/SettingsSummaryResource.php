<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Catalog\Actions\ShowSettingsSummaryAction;

/**
 * How Showroom's API and MCP server are set up, for an admin panel.
 *
 * @phpstan-import-type Summary from ShowSettingsSummaryAction
 */
final class SettingsSummaryResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'authorization' => $schema->boolean()->description('Whether calls act as the signed-in user and are checked against showroom.policies.')->required(),
            'routes' => $schema->object([
                'enabled' => $schema->boolean()->required(),
                'prefix' => $schema->string()->required(),
                'middleware' => $schema->array()->items($schema->string())->description('Middleware names.')->required(),
            ])->description('The JSON API routes.')->required(),
            'mcp' => $schema->object([
                'web' => $schema->object([
                    'enabled' => $schema->boolean()->required(),
                    'route' => $schema->string()->required(),
                    'middleware' => $schema->array()->items($schema->string())->description('Middleware names.')->required(),
                ])->required(),
                'local' => $schema->object([
                    'enabled' => $schema->boolean()->required(),
                    'handle' => $schema->string()->required(),
                ])->required(),
            ])->description('The MCP server\'s web and local transports.')->required(),
            'pagination' => $schema->object([
                'per_page' => $schema->integer()->required(),
                'max_per_page' => $schema->integer()->required(),
            ])->description('Page sizes for every listing.')->required(),
        ];
    }

    /**
     * @return Summary
     */
    public function toArray(Request $request): array
    {
        /** @var Summary $summary */
        $summary = $this->resource;

        return $summary;
    }
}
