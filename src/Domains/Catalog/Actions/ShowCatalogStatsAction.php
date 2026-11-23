<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Actions;

use Illuminate\Support\Facades\Cache;
use RefactorCircus\Showroom\Domains\Catalog\Data\CatalogStats;
use RefactorCircus\Showroom\Domains\Catalog\Events\CatalogStatsShowingActionEvent;
use RefactorCircus\Showroom\Domains\Catalog\Events\CatalogStatsShownActionEvent;
use RefactorCircus\Showroom\Domains\Product\Enums\ProductStatus;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\CompletenessModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;
use RefactorCircus\Showroom\Support\Tables;

final class ShowCatalogStatsAction
{
    /**
     * Where the completeness rows are kept between reads.
     */
    public const string COMPLETENESS_CACHE_KEY = 'showroom:stats:completeness';

    /**
     * How many products the review queue lists.
     */
    private const int REVIEW_QUEUE = 5;

    /**
     * How many versions the latest changes list.
     */
    private const int RECENT_VERSIONS = 8;

    /**
     * The catalog's figures, in a fixed number of queries however large it
     * is. Only the completeness rows, which scan every stored score, are
     * cached, for `showroom.stats.cache_seconds`.
     */
    public function execute(): CatalogStats
    {
        CatalogStatsShowingActionEvent::dispatch();

        $stats = $this->perform();

        CatalogStatsShownActionEvent::dispatch($stats);

        return $stats;
    }

    private function perform(): CatalogStats
    {
        $status = $this->statusCounts();

        return new CatalogStats(
            statusCounts: $status['counts'],
            published: $status['published'],
            completeness: $this->completeness(),
            reviewQueue: ProductModel::query()
                ->where('status', ProductStatus::InReview)
                ->oldest('updated_at')
                ->orderBy('id')
                ->limit(self::REVIEW_QUEUE)
                ->get(),
            // Every product in review, from the status counts.
            reviewTotal: $status['counts'][ProductStatus::InReview->value],
            recentVersions: VersionModel::query()
                ->with(['versionable', 'author'])
                ->latest('created_at')
                ->orderByDesc('id')
                ->limit(self::RECENT_VERSIONS)
                ->get(),
        );
    }

    /**
     * Products per status, and how many are live, in one query.
     *
     * @return array{counts: array<string, int>, published: int}
     */
    private function statusCounts(): array
    {
        $rows = ProductModel::query()
            ->toBase()
            ->select('status')
            ->selectRaw('count(*) as products, sum(case when published_version is null then 0 else 1 end) as live')
            ->groupBy('status')
            ->get()
            ->keyBy(fn (object $row): string => (string) data_get($row, 'status'));

        $counts = [];

        foreach (ProductStatus::cases() as $status) {
            $counts[$status->value] = (int) data_get($rows->get($status->value), 'products', 0);
        }

        return [
            'counts' => $counts,
            'published' => (int) $rows->sum(fn (object $row): int => (int) data_get($row, 'live', 0)),
        ];
    }

    /**
     * The completeness rows, kept for `showroom.stats.cache_seconds` (0
     * reads them fresh every time).
     *
     * @return array<int, array{channel: string, locale: string, products: int, complete: int, average: int}>
     */
    private function completeness(): array
    {
        $seconds = max(0, (int) config('showroom.stats.cache_seconds', 60));

        if ($seconds === 0) {
            return $this->completenessRows();
        }

        /** @var array<int, array{channel: string, locale: string, products: int, complete: int, average: int}> $rows */
        $rows = Cache::remember(self::COMPLETENESS_CACHE_KEY, $seconds, fn (): array => $this->completenessRows());

        return $rows;
    }

    /**
     * Completeness per channel and locale, by channel code then locale code.
     *
     * @return array<int, array{channel: string, locale: string, products: int, complete: int, average: int}>
     */
    private function completenessRows(): array
    {
        $scores = Tables::name('product_completeness');
        $channels = Tables::name('channels');
        $locales = Tables::name('locales');

        return CompletenessModel::query()
            ->join($channels, $channels.'.id', '=', $scores.'.channel_id')
            ->join($locales, $locales.'.id', '=', $scores.'.locale_id')
            ->groupBy($channels.'.code', $locales.'.code')
            ->orderBy($channels.'.code')
            ->orderBy($locales.'.code')
            ->select([$channels.'.code as channel', $locales.'.code as locale'])
            ->selectRaw('count(*) as products, sum(case when ratio = 100 then 1 else 0 end) as complete, avg(ratio) as average')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'channel' => (string) data_get($row, 'channel'),
                'locale' => (string) data_get($row, 'locale'),
                'products' => (int) data_get($row, 'products'),
                'complete' => (int) data_get($row, 'complete'),
                'average' => (int) round((float) data_get($row, 'average')),
            ])
            ->values()
            ->all();
    }
}
