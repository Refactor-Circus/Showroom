@php($uiLocale = app()->getLocale())

<x-atrium::layout :title="$channel->label()">
    <x-atrium::page-header :title="$channel->label()" :description="$channel->code">
        <x-slot:actions>
            @showroomCan('delete', $channel)
                <form method="POST" action="{{ route('atrium.showroom.channels.destroy', $channel) }}">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="trash" :label="__('showroom::showroom.delete')" variant="danger" type="submit" data-testid="delete-channel" />
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

            @showroomCan('update', $channel)
            <x-atrium::card data-testid="channel-details-card" :title="__('showroom::showroom.details')">
                <form method="POST" action="{{ route('atrium.showroom.channels.update', $channel) }}" class="flex flex-col gap-4">
                    @csrf
                    @method('PATCH')
                    <x-atrium::form.input :name="'labels['.$uiLocale.']'" :label="__('showroom::showroom.label_field', ['locale' => $uiLocale])" :value="$channel->labels[$uiLocale] ?? null" wrapper="w-64" />
                    @include('showroom::ui.channels.fields')
                    <div>
                        <x-atrium::icon-button icon="check" :label="__('showroom::showroom.save')" variant="primary" type="submit" data-testid="save-channel" />
                    </div>
                </form>
            </x-atrium::card>
            @endshowroomCan

            <p class="text-sm text-on-surface dark:text-on-surface-dark">{{ __('showroom::showroom.channel_delete_note') }}</p>
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="aside" class="mt-5" />

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
