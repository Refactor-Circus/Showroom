{{--
    A record's effective flags, read-only: its own, and those it inherits from
    the owners above it, which it cannot drop. Inherited ones are marked.
--}}
@php($ownCodes = $record->flags->pluck('code')->all())

<div data-testid="effective-flags">
    <p class="mb-1 text-xs font-medium uppercase opacity-70">{{ __('showroom::showroom.effective_flags') }}</p>
    <ul class="flex flex-wrap gap-3">
        @forelse ($record->loadedEffectiveFlags() ?? [] as $flag)
            @php($inherited = ! in_array($flag->code, $ownCodes, true))
            <li class="flex items-center gap-1.5 text-sm" data-effective-flag="{{ $flag->code }}" data-inherited="{{ $inherited ? 'true' : 'false' }}">
                <x-atrium::badge :variant="$inherited ? 'neutral' : 'primary'">{{ $flag->code }}</x-atrium::badge>
                @if ($inherited)
                    <span class="text-xs opacity-60">{{ __('showroom::showroom.inherited') }}</span>
                @endif
            </li>
        @empty
            <li class="text-sm opacity-60">{{ __('showroom::showroom.none') }}</li>
        @endforelse
    </ul>
</div>
