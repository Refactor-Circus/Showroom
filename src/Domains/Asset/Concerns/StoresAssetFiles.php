<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Asset\Data\StoredFile;
use RefactorCircus\Showroom\Domains\Asset\Services\AssetStorage;

/**
 * Taking a file in from an upload, a path on the disk or a URL, shared by
 * creating and replacing an asset.
 */
trait StoresAssetFiles
{
    /**
     * Store the source the data names, or null when it names none.
     *
     * @throws ValidationException
     */
    private function storeSource(AssetStorage $storage, ?UploadedFile $file, ?string $path, ?string $url): ?StoredFile
    {
        $stored = match (true) {
            $file !== null => $storage->storeUpload($file),
            $path !== null => $storage->adopt($path),
            $url !== null => $storage->storeFromUrl($url),
            default => null,
        };

        if ($stored !== null && $path === null) {
            $this->checkLimits($storage, $stored, $url !== null ? 'url' : 'file');
        } elseif ($stored !== null) {
            $this->checkLimits(null, $stored, 'path');
        }

        return $stored;
    }

    /**
     * Uploads are checked by their rules; a fetched or adopted file only once
     * its size and type are known.
     *
     * @throws ValidationException
     */
    private function checkLimits(?AssetStorage $storage, StoredFile $stored, string $key): void
    {
        $max = (int) config('showroom.media.max_kilobytes', 51200) * 1024;

        /** @var array<int, string>|null $types */
        $types = config('showroom.media.mime_types');

        $error = match (true) {
            $stored->size > $max => sprintf('The file is %d KB; the limit is %d KB.', intdiv($stored->size, 1024), intdiv($max, 1024)),
            is_array($types) && $types !== [] && ! in_array($stored->mimeType, $types, true) => sprintf('Files of type "%s" are not accepted.', $stored->mimeType ?? 'unknown'),
            default => null,
        };

        if ($error === null) {
            return;
        }

        // Only discard what Showroom wrote; an adopted file is the caller's.
        $storage?->discard($stored);

        throw ValidationException::withMessages([$key => $error]);
    }
}
