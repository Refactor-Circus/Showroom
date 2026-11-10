<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Support;

use Illuminate\Http\Request;

/**
 * The dashboard edits a record's external ids as a map: one input per source
 * it holds, which an empty value removes, and one new source and id to add.
 * The Actions take the `{source: id}` map, where null removes a source and
 * sources left out are kept. When each source last synced is shown, never
 * sent: the Actions stamp it.
 */
final class ExternalIdForm
{
    /**
     * The `external_ids` map the form sent, or null when it sent none.
     *
     * @return array<string, string|null>|null
     */
    public static function fromForm(Request $request): ?array
    {
        if (! $request->has('external_ids') && ! $request->has('external_ids_new')) {
            return null;
        }

        $map = [];

        foreach ((array) $request->input('external_ids', []) as $source => $externalId) {
            $map[(string) $source] = is_string($externalId) && $externalId !== '' ? $externalId : null;
        }

        $source = $request->input('external_ids_new.source');
        $externalId = $request->input('external_ids_new.id');

        if (is_string($source) && $source !== '' && is_string($externalId) && $externalId !== '') {
            $map[$source] = $externalId;
        }

        return $map;
    }
}
