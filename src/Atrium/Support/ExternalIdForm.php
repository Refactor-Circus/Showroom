<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Support;

use Illuminate\Http\Request;

/**
 * The dashboard edits a record's external ids as a map: one input per system
 * it holds, which an empty value removes, and one new system and id to add.
 * The Actions take the `{system: id}` map, where null removes a system and
 * systems left out are kept.
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

        foreach ((array) $request->input('external_ids', []) as $system => $externalId) {
            $map[(string) $system] = is_string($externalId) && $externalId !== '' ? $externalId : null;
        }

        $system = $request->input('external_ids_new.system');
        $externalId = $request->input('external_ids_new.id');

        if (is_string($system) && $system !== '' && is_string($externalId) && $externalId !== '') {
            $map[$system] = $externalId;
        }

        return $map;
    }
}
