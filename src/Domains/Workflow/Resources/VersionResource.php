<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;

/**
 * One version of a product, with its full snapshot.
 *
 * @mixin VersionModel
 */
final class VersionResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return VersionSummaryResource::schema($schema) + [
            'snapshot' => $schema->object()->description('The product as it was at this version.')->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return (new VersionSummaryResource($this->resource))->resolve($request) + [
            'snapshot' => $this->snapshot,
        ];
    }
}
