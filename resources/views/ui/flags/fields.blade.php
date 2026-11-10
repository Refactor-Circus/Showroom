{{-- The fields of a flag; shared by the create and edit forms. An empty applies_to[] precedes the boxes, so unticking both is sent and refused rather than omitted. --}}
<div class="flex flex-wrap items-start gap-3">
    <div>
        <p class="mb-1 text-xs font-medium uppercase opacity-70">{{ __('showroom::showroom.applies_to') }}</p>
        <input type="hidden" name="applies_to[]" value="">
        <div class="flex flex-wrap gap-3">
            @foreach ($subjects as $subject)
                <label class="flex items-center gap-1.5 text-sm">
                    <x-atrium::form.checkbox bare :id="'atrium-applies_to-'.$subject" name="applies_to[]" :value="$subject" :checked="in_array($subject, $flag?->applies_to ?? $subjects, true)" />
                    <span>{{ __('showroom::showroom.flag_subject_'.$subject) }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <x-atrium::form.input name="sort_order" type="number" min="0" :label="__('showroom::showroom.sort_order')" :value="$flag?->sort_order" wrapper="w-44" />
</div>
