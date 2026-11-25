<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Catalog\Data\CatalogStats;
use RefactorCircus\Showroom\Domains\Product\Enums\ProductStatus;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;
use RefactorCircus\Showroom\Domains\Workflow\Resources\VersionSummaryResource;

/**
 * The catalog at a glance: products per status, completeness per channel
 * and locale, the review queue and the latest versions.
 *
 * @mixin CatalogStats
 */
final class CatalogStatsResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        $statuses = array_column(ProductStatus::cases(), 'value');
        $counts = [];

        foreach ($statuses as $status) {
            $counts[$status] = $schema->integer()->required();
        }

        return [
            'status' => $schema->object([
                'counts' => $schema->object($counts)->description('Products per status; 0 for a status without products.')->required(),
                'published' => $schema->integer()->description('Products with a published version.')->required(),
            ])->required(),
            'completeness' => $schema->array()->items($schema->object([
                'channel' => $schema->string()->description('The channel code.')->required(),
                'locale' => $schema->string()->description('The locale code.')->required(),
                'products' => $schema->integer()->description('Products scored for the channel and locale.')->required(),
                'complete' => $schema->integer()->description('Products 100% complete.')->required(),
                'average' => $schema->integer()->description('The mean ratio, rounded.')->required(),
            ]))->description('One row per channel and locale with stored scores, by channel code then locale code.')->required(),
            'review_queue' => $schema->object([
                'products' => $schema->array()->items($schema->object([
                    'id' => $schema->string()->required(),
                    'identifier' => $schema->string()->required(),
                    'slug' => $schema->string()->required(),
                    'status' => $schema->string()->enum($statuses)->required(),
                    'updated_at' => $schema->string()->nullable()->required(),
                ]))->description('The products in review the longest, oldest first.')->required(),
                'total' => $schema->integer()->description('Every product in review.')->required(),
            ])->required(),
            'recent_versions' => $schema->array()->items($schema->object(VersionSummaryResource::schema($schema) + [
                'subject' => $schema->object([
                    'type' => $schema->string()->enum(['product', 'product_model'])->required(),
                    'id' => $schema->string()->required(),
                    'key' => $schema->string()->nullable()->description('The product\'s identifier or the product model\'s code; null once it is gone.')->required(),
                ])->required(),
            ]))->description('The newest versions, newest first.')->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CatalogStats $stats */
        $stats = $this->resource;

        return [
            'status' => [
                'counts' => $stats->statusCounts,
                'published' => $stats->published,
            ],
            'completeness' => $stats->completeness,
            'review_queue' => [
                'products' => $stats->reviewQueue
                    ->map(fn (ProductModel $product): array => [
                        'id' => $product->id,
                        'identifier' => $product->identifier,
                        'slug' => $product->slug,
                        'status' => $product->status->value,
                        'updated_at' => $product->updated_at?->toIso8601String(),
                    ])
                    ->values()
                    ->all(),
                'total' => $stats->reviewTotal,
            ],
            'recent_versions' => $stats->recentVersions
                ->map(fn (VersionModel $version): array => (new VersionSummaryResource($version))->resolve($request) + [
                    'subject' => $this->subject($version),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * What a version is a version of, from its loaded `versionable`.
     *
     * @return array{type: string, id: string, key: string|null}
     */
    private function subject(VersionModel $version): array
    {
        $class = Relation::getMorphedModel($version->versionable_type) ?? $version->versionable_type;
        $subject = $version->versionable;

        return [
            'type' => $class === ProductModelModel::class ? 'product_model' : 'product',
            'id' => $version->versionable_id,
            'key' => match (true) {
                $subject instanceof ProductModel => $subject->identifier,
                $subject instanceof ProductModelModel => $subject->code,
                default => null,
            },
        ];
    }
}
