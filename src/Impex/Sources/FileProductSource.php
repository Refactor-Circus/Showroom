<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Sources;

use Illuminate\Support\Facades\Storage;
use RefactorCircus\Impex\Domains\Batch\Contracts\BatchSource;
use RefactorCircus\Impex\Domains\Batch\Data\BatchChunk;
use RefactorCircus\Impex\Domains\Batch\Data\BatchChunkItem;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Impex\ProductRows;
use RuntimeException;

/**
 * Streams product records out of a `.csv` or `.jsonl` asset, a chunk at a
 * time.
 *
 * Each batch item is a chunk of `$chunkSize` records, written by one call to
 * UpsertProductChunk. Its key is its first record's byte offset, and its
 * `start` that record's row: the position among the file's records, from 0,
 * leaving out a CSV header and blank lines. The cursor carries both, as
 * `{offset}:{row}`. The file does not change under a run, so a redelivered
 * page maps to the same chunks.
 */
final class FileProductSource implements BatchSource
{
    public function __construct(
        private readonly string $asset,
        private readonly string $format,
        private readonly string $mode,
        private readonly bool $captureChanges = true,
        private readonly int $chunkSize = 500,
    ) {}

    /**
     * @param  int  $size  How many chunks to yield.
     */
    public function chunk(?string $cursor, int $size): BatchChunk
    {
        $asset = AssetModel::query()->where('code', $this->asset)->firstOrFail();
        $stream = Storage::disk($asset->disk)->readStream($asset->path);

        if (! is_resource($stream)) {
            throw new RuntimeException(sprintf('The file of asset "%s" cannot be read.', $asset->code));
        }

        try {
            [$offset, $row] = self::position($cursor);

            $header = $this->format === 'csv' ? $this->header($stream) : null;
            $this->skipTo($stream, $offset);

            $items = [];

            while (count($items) < $size) {
                $first = null;
                $start = $row;
                $records = [];

                while (count($records) < max(1, $this->chunkSize)) {
                    $at = (int) ftell($stream);
                    $record = $this->next($stream, $header);

                    if ($record === false) {
                        if ($records !== []) {
                            $items[] = $this->item((int) $first, $start, $records);
                        }

                        return BatchChunk::last($items);
                    }

                    if ($record === null) {
                        continue;
                    }

                    $first ??= $at;
                    $records[] = $record;
                    $row++;
                }

                $items[] = $this->item((int) $first, $start, $records);
            }

            return BatchChunk::of($items, (int) ftell($stream).':'.$row);
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  list<array<string, mixed>|string>  $records
     */
    private function item(int $offset, int $start, array $records): BatchChunkItem
    {
        return new BatchChunkItem((string) $offset, [
            'mode' => $this->mode,
            'capture_changes' => $this->captureChanges,
            'start' => $start,
            'records' => $records,
        ]);
    }

    /**
     * The byte offset to read from and the row it holds, from a cursor.
     *
     * @return array{0: int|null, 1: int}
     */
    private static function position(?string $cursor): array
    {
        if ($cursor === null) {
            return [null, 0];
        }

        [$offset, $row] = array_pad(explode(':', $cursor, 2), 2, '0');

        return [(int) $offset, (int) $row];
    }

    /**
     * @param  resource  $stream
     * @return array<int, string>
     */
    private function header(mixed $stream): array
    {
        $header = fgetcsv($stream, escape: '\\');

        if ($header === false) {
            return [];
        }

        // A UTF-8 byte order mark would stick to the first column's name.
        $header[0] = ltrim((string) $header[0], "\u{FEFF}");

        return array_map(fn (?string $column): string => trim((string) $column), $header);
    }

    /**
     * Move to the cursor. Object stores often hand out streams that cannot
     * seek, so those are read forward instead.
     *
     * @param  resource  $stream
     */
    private function skipTo(mixed $stream, ?int $offset): void
    {
        if ($offset === null || $offset <= (int) ftell($stream)) {
            return;
        }

        if (stream_get_meta_data($stream)['seekable']) {
            fseek($stream, $offset);

            return;
        }

        while ((int) ftell($stream) < $offset && ! feof($stream)) {
            fread($stream, max(1, min(1048576, $offset - (int) ftell($stream))));
        }
    }

    /**
     * The next record, null for a blank line, false at the end. A line that
     * is not a JSON object is returned as it is, so it fails visibly, on its
     * own row.
     *
     * @param  resource  $stream
     * @param  array<int, string>|null  $header
     * @return array<string, mixed>|string|false|null
     */
    private function next(mixed $stream, ?array $header): array|string|false|null
    {
        if ($header !== null) {
            $cells = fgetcsv($stream, escape: '\\');

            if ($cells === false) {
                return false;
            }

            if ($cells === [null]) {
                return null;
            }

            $row = [];

            foreach ($header as $index => $column) {
                $row[$column] = $cells[$index] ?? null;
            }

            return app(ProductRows::class)->fromCsv($row);
        }

        $line = fgets($stream);

        if ($line === false) {
            return false;
        }

        $line = trim($line);

        if ($line === '') {
            return null;
        }

        $record = json_decode($line, true);

        return is_array($record) && ($record === [] || ! array_is_list($record)) ? $record : $line;
    }
}
