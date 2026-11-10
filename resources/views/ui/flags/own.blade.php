@use(RefactorCircus\Showroom\Atrium\FlagScreen)

{{--
    A record's own flags, for those who may assign them: a box per flag that
    applies to the record. The hidden marker says the field was sent, so
    unticking every box clears the set.
--}}
@php($ownCodes = $record->flags->pluck('code')->all())

<div data-testid="own-flags">
    <p class="mb-1 text-xs font-medium uppercase opacity-70">{{ __('showroom::showroom.own_flags') }}</p>
    <input type="hidden" name="{{ FlagScreen::SENT }}" value="1">
    <div class="flex flex-wrap gap-3">
        @forelse ($flagOptions as $option)
            <label class="flex items-center gap-1.5 text-sm">
                <x-atrium::form.checkbox bare :id="'atrium-flags-'.$option->code" name="flags[]" :value="$option->code" :checked="in_array($option->code, $ownCodes, true)" />
                <span class="font-mono text-xs">{{ $option->code }}</span>
                @if ($option->label() !== $option->code)
                    <span class="opacity-70">{{ $option->label() }}</span>
                @endif
            </label>
        @empty
            <span class="text-sm opacity-60">{{ __('showroom::showroom.no_flags') }}</span>
        @endforelse
    </div>
</div>
