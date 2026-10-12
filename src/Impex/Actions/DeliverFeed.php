<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Actions;

use Illuminate\Support\Facades\Storage;
use RefactorCircus\Impex\Domains\Channel\Data\OutboundMessage;
use RefactorCircus\Impex\Impex;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Transfer\Events\FeedDeliveredActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\FeedDeliveringActionEvent;
use RuntimeException;

/**
 * Sends a feed file to its destination through Impex, so the delivery lands
 * in the ledger with its run and response.
 *
 * Through a named outbound channel (`deliver_through`), the channel decides
 * how: its transport (http, file, a partner's SFTP), signing, headers and
 * body policy. Otherwise the file is POSTed to the feed's URL on Impex's
 * recorded HTTP client.
 */
final class DeliverFeed
{
    public function __construct(private readonly Impex $impex) {}

    /**
     * @return array{status: int|null}
     */
    public function execute(string $asset, string $url, string $channel, string $runId, ?string $through = null): array
    {
        FeedDeliveringActionEvent::dispatch($asset, $url, $channel, $runId, $through);

        $result = $this->perform($asset, $url, $channel, $runId, $through);

        FeedDeliveredActionEvent::dispatch($asset, $runId, $result['status']);

        return $result;
    }

    /**
     * @return array{status: int|null}
     */
    private function perform(string $asset, string $url, string $channel, string $runId, ?string $through): array
    {
        $file = AssetModel::query()->where('code', $asset)->firstOrFail();
        $stream = Storage::disk($file->disk)->readStream($file->path);

        if (! is_resource($stream)) {
            throw new RuntimeException(sprintf('The file of asset "%s" cannot be read.', $asset));
        }

        if ($through !== null) {
            return $this->through($through, $stream, $file, $url, $runId);
        }

        // The HTTP client takes the stream over and closes it.
        $response = $this->impex->http($channel, $runId)
            ->withHeaders(['Content-Type' => $file->mime_type ?? 'application/octet-stream'])
            ->send('POST', $url, ['body' => $stream]);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf('The feed destination answered with status %d.', $response->status()));
        }

        return ['status' => $response->status()];
    }

    /**
     * @param  resource  $stream
     * @return array{status: int|null}
     */
    private function through(string $channel, mixed $stream, AssetModel $file, string $url, string $runId): array
    {
        try {
            $receipt = $this->impex->send($channel, new OutboundMessage(
                stream: $stream,
                headers: ['Content-Type' => $file->mime_type ?? 'application/octet-stream'],
                endpoint: $url === '' ? null : $url,
                id: $file->code,
                links: ['run_id' => $runId],
            ));
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if ($receipt->failed()) {
            throw new RuntimeException(sprintf(
                'The feed could not be delivered through channel "%s": %s',
                $channel,
                is_string($receipt->error['message'] ?? null) ? $receipt->error['message'] : 'unknown error',
            ));
        }

        return ['status' => $receipt->statusCode];
    }
}
