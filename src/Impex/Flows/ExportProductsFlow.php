<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Flows;

use RefactorCircus\Impex\Domains\Flow\Support\Flow;
use RefactorCircus\Showroom\Impex\Actions\ExportProductPages;
use RefactorCircus\Showroom\Impex\Actions\JoinProductExport;

/**
 * `showroom:export-products` — the products a search matches, to a `.jsonl`
 * or `.csv` asset. With `published`, each product's live version is written
 * instead of its working copy, and unpublished products are left out.
 */
final class ExportProductsFlow extends Flow
{
    /**
     * @param  array<string, mixed>  $query  ListProductsData input, plus `scope` and `locales`.
     * @return array{asset: string, count: int, format: string}
     */
    public function handle(array $query = [], string $format = 'jsonl', ?string $code = null, bool $published = false): array
    {
        $this->tag('showroom', 'export');

        $runId = $this->context()->run->id;

        $pages = $this->action(ExportProductPages::class, $query, $runId, $published)->run();

        return $this->action(
            JoinProductExport::class,
            $runId,
            (int) $pages['parts'],
            (int) $pages['count'],
            $format,
            $code ?? 'export-'.strtolower($runId),
        )->run();
    }
}
