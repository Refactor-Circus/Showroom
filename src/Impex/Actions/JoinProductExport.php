<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Actions;

use Generator;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RefactorCircus\Showroom\Domains\Asset\Actions\CreateAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Data\CreateAssetData;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Asset\Services\AssetStorage;
use RefactorCircus\Showroom\Domains\Transfer\Events\ProductExportJoinedActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\ProductExportJoiningActionEvent;
use RefactorCircus\Showroom\Impex\ProductRows;
use RuntimeException;

/**
 * Stitches an export's part files into one `.jsonl` or `.csv` file and makes
 * it an asset, so it can be downloaded, linked or delivered.
 */
final class JoinProductExport
{
    /**
     * @return array{asset: string, count: int, format: string}
     */
    public function execute(string $runId, int $parts, int $count, string $format, string $code): array
    {
        ProductExportJoiningActionEvent::dispatch($runId, $parts, $format, $code);

        $result = $this->perform($runId, $parts, $count, $format, $code);

        ProductExportJoinedActionEvent::dispatch($runId, $result['asset'], $result['count'], $result['format']);

        return $result;
    }

    /**
     * @return array{asset: string, count: int, format: string}
     */
    private function perform(string $runId, int $parts, int $count, string $format, string $code): array
    {
        // A redelivered step finds its own asset already made.
        if (AssetModel::query()->where('code', $code)->exists()) {
            return ['asset' => $code, 'count' => $count, 'format' => $format];
        }

        $disk = Storage::disk(app(AssetStorage::class)->diskName());
        $partPaths = array_values(array_filter(
            array_map(fn (int $page): string => ExportProductPages::part($runId, $page), range(1, max(1, $parts))),
            fn (string $path): bool => $disk->exists($path),
        ));

        // Spooled: memory first, a temporary file past 8MB.
        $out = fopen('php://temp/maxmemory:8388608', 'w+b');

        if ($out === false) {
            throw new RuntimeException('Could not open a buffer for the export.');
        }

        $format === 'csv' ? $this->writeCsv($disk, $partPaths, $out) : $this->writeJsonl($disk, $partPaths, $out);

        rewind($out);

        $path = trim((string) config('showroom.impex.export_path', 'showroom/exports'), '/').'/'.$runId.'/'.$code.'.'.$format;
        $disk->writeStream($path, $out);
        fclose($out);

        // The parts go once the asset is recorded, so a failed step can
        // be run again from them.
        DB::transaction(function () use ($code, $path, $disk, $partPaths): void {
            app(CreateAssetAction::class)->execute(new CreateAssetData(code: $code, path: $path));

            DB::afterCommit(fn (): bool => $disk->delete($partPaths));
        });

        return ['asset' => $code, 'count' => $count, 'format' => $format];
    }

    /**
     * @param  array<int, string>  $parts
     * @param  resource  $out
     */
    private function writeJsonl(Filesystem $disk, array $parts, mixed $out): void
    {
        foreach ($parts as $part) {
            $in = $disk->readStream($part);

            if (is_resource($in)) {
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
        }
    }

    /**
     * Two passes: the columns of every record first, then the rows, so each
     * row lines up under one header.
     *
     * @param  array<int, string>  $parts
     * @param  resource  $out
     */
    private function writeCsv(Filesystem $disk, array $parts, mixed $out): void
    {
        $rows = app(ProductRows::class);
        $columns = ['identifier', 'family', 'parent', 'owner', 'enabled', 'categories'];

        foreach ($this->records($disk, $parts) as $record) {
            foreach (array_keys($rows->toCsv($record)) as $column) {
                if (! in_array($column, $columns, true)) {
                    $columns[] = $column;
                }
            }
        }

        $fixed = array_slice($columns, 0, 6);
        $rest = array_slice($columns, 6);
        sort($rest);
        $columns = [...$fixed, ...$rest];

        fputcsv($out, $columns, escape: '\\');

        foreach ($this->records($disk, $parts) as $record) {
            $row = $rows->toCsv($record);

            fputcsv($out, array_map(fn (string $column): string => $row[$column] ?? '', $columns), escape: '\\');
        }
    }

    /**
     * @param  array<int, string>  $parts
     * @return Generator<int, array<string, mixed>>
     */
    private function records(Filesystem $disk, array $parts): Generator
    {
        foreach ($parts as $part) {
            $in = $disk->readStream($part);

            if (! is_resource($in)) {
                continue;
            }

            while (($line = fgets($in)) !== false) {
                $record = json_decode(trim($line), true);

                if (is_array($record)) {
                    yield $record;
                }
            }

            fclose($in);
        }
    }
}
