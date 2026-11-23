<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Data;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;

/**
 * The catalog's figures at a glance: products per status and how many are
 * live, completeness per channel and locale, the review queue and the
 * latest versions.
 */
final readonly class CatalogStats
{
    /**
     * @param  array<string, int>  $statusCounts  Status value => products, every status listed.
     * @param  array<int, array{channel: string, locale: string, products: int, complete: int, average: int}>  $completeness  By channel code, then locale code.
     * @param  EloquentCollection<int, ProductModel>  $reviewQueue  The products waiting longest for review.
     * @param  EloquentCollection<int, VersionModel>  $recentVersions  Newest first, with `versionable` and `author` loaded.
     */
    public function __construct(
        public array $statusCounts,
        public int $published,
        public array $completeness,
        public EloquentCollection $reviewQueue,
        public int $reviewTotal,
        public EloquentCollection $recentVersions,
    ) {}
}
