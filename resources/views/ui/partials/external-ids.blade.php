{{--
    A record's ids in other systems, edited as a map: an input per system it
    holds (empty removes it) and one new system and id to add.
--}}
<div data-testid="external-ids">
    <p class="mb-1 text-xs font-medium uppercase opacity-70">{{ __('showroom::showroom.external_ids') }}</p>
    <div class="flex flex-col gap-3">
        @foreach ($externalIds as $system => $externalId)
            <div class="flex flex-wrap items-center gap-3" data-external-system="{{ $system }}">
                <span class="w-44 font-mono text-xs">{{ $system }}</span>
                <x-atrium::form.input bare :id="'atrium-external_ids-'.$system" :name="'external_ids['.$system.']'" :value="$externalId" :aria-label="$system" wrapper="w-56" />
            </div>
        @endforeach

        <div class="flex flex-wrap items-start gap-3">
            <x-atrium::form.input id="atrium-external_ids_new-system" name="external_ids_new[system]" :label="__('showroom::showroom.external_system')" :hint="__('showroom::showroom.external_system_hint')" wrapper="w-44" />
            <x-atrium::form.input id="atrium-external_ids_new-id" name="external_ids_new[id]" :label="__('showroom::showroom.external_id')" wrapper="w-56" />
        </div>
    </div>
</div>
