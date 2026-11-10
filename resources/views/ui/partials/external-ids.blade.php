{{--
    A record's ids in other sources, edited as a map: an input per source it
    holds (empty removes it) and one new source and id to add. When each
    source last synced its id is shown read-only; the Actions stamp it.

    $externalIds: the record's external id rows (its externalIds relation, by source).
--}}
<div data-testid="external-ids">
    <p class="mb-1 text-xs font-medium uppercase opacity-70">{{ __('showroom::showroom.external_ids') }}</p>
    <div class="flex flex-col gap-3">
        @foreach ($externalIds as $row)
            <div class="flex flex-wrap items-center gap-3" data-external-source="{{ $row->source }}">
                <span class="w-44 font-mono text-xs">{{ $row->source }}</span>
                <x-atrium::form.input bare :id="'atrium-external_ids-'.$row->source" :name="'external_ids['.$row->source.']'" :value="$row->external_id" :aria-label="$row->source" wrapper="w-56" />
                @if ($row->synced_at !== null)
                    <time class="text-xs opacity-70" data-external-synced="{{ $row->source }}" datetime="{{ $row->synced_at->toIso8601String() }}" title="{{ $row->synced_at->toIso8601String() }}">{{ __('showroom::showroom.external_synced', ['time' => $row->synced_at->diffForHumans()]) }}</time>
                @else
                    <span class="text-xs opacity-70" data-external-synced="{{ $row->source }}">{{ __('showroom::showroom.external_never_synced') }}</span>
                @endif
            </div>
        @endforeach

        <div class="flex flex-wrap items-start gap-3">
            <x-atrium::form.input id="atrium-external_ids_new-source" name="external_ids_new[source]" :label="__('showroom::showroom.external_source')" :hint="__('showroom::showroom.external_source_hint')" wrapper="w-44" />
            <x-atrium::form.input id="atrium-external_ids_new-id" name="external_ids_new[id]" :label="__('showroom::showroom.external_id')" wrapper="w-56" />
        </div>
    </div>
</div>
