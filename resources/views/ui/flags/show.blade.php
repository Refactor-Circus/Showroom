@php($locale = app()->getLocale())

<x-atrium::layout :title="$flag->label()">
    <x-atrium::page-header :title="$flag->label()" :description="$flag->code">
        <x-slot:actions>
            @showroomCan('delete', $flag)
                <form method="POST" action="{{ route('atrium.showroom.flags.destroy', $flag) }}">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="trash" :label="__('showroom::showroom.delete')" variant="danger" type="submit" data-testid="delete-flag" />
                </form>
            @endshowroomCan

            <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
        </x-slot:actions>
    </x-atrium::page-header>

    <x-atrium::screen-tabs :screen="$resolved" class="mt-4" />

    @if ($resolved->openRegisteredTab !== null)
        <x-atrium::screen-tab-panel :screen="$resolved" class="mt-5" />
    @else
        <x-atrium::screen-sections :screen="$resolved" slot="before" class="mt-5" />

        <div class="mt-5 flex flex-col gap-5">
            <x-atrium::flash />

            <x-atrium::card>
                <x-atrium::description-list>
                    {{-- Assignments, search filters and API clients name a flag by its code, so it is never edited. --}}
                    <x-atrium::description-list.item :term="__('showroom::showroom.code')" class="font-mono" data-testid="flag-code">{{ $flag->code }}</x-atrium::description-list.item>
                </x-atrium::description-list>
            </x-atrium::card>

            @showroomCan('update', $flag)
            <x-atrium::card data-testid="flag-details-card" :title="__('showroom::showroom.details')">
                <form method="POST" action="{{ route('atrium.showroom.flags.update', $flag) }}" class="flex flex-col gap-4">
                    @csrf
                    @method('PATCH')
                    <x-atrium::form.input :name="'labels['.$locale.']'" :label="__('showroom::showroom.label_field', ['locale' => $locale])" :value="$flag->labels[$locale] ?? null" wrapper="w-64" />

                    @include('showroom::ui.flags.fields')

                    <div>
                        <x-atrium::icon-button icon="check" :label="__('showroom::showroom.save')" variant="primary" type="submit" data-testid="save-flag" />
                    </div>
                </form>
            </x-atrium::card>
            @endshowroomCan

            <p class="text-sm text-on-surface dark:text-on-surface-dark">{{ __('showroom::showroom.flag_delete_note') }}</p>
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="aside" class="mt-5" />

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
