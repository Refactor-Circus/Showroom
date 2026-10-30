@use(RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel)
@php($uiLocale = app()->getLocale())

<x-atrium::layout :title="__('showroom::showroom.channels')">
    <x-atrium::page-header :title="__('showroom::showroom.channels')" :description="__('showroom::showroom.channels_description')">
        @if ($resolved->sections('header.actions') !== [])
            <x-slot:actions>
                <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
            </x-slot:actions>
        @endif
    </x-atrium::page-header>

    <x-atrium::screen-tabs :screen="$resolved" class="mt-4" />

    @if ($resolved->openRegisteredTab !== null)
        <x-atrium::screen-tab-panel :screen="$resolved" class="mt-5" />
    @else
        <x-atrium::screen-sections :screen="$resolved" slot="before" class="mt-5" />

        <div class="mt-5 flex flex-col gap-4">
            <x-atrium::flash />

            @showroomCan('viewAny', \RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel::class)
            <x-atrium::card :title="__('showroom::showroom.locales')" data-testid="locales-card">
                @if ($locales->isNotEmpty())
                    <ul class="mb-4 flex flex-wrap gap-3">
                        @foreach ($locales as $locale)
                            <li class="flex items-center gap-2 rounded-radius border border-outline px-2 py-1 text-sm dark:border-outline-dark" data-locale="{{ $locale->code }}">
                                <span class="font-mono">{{ $locale->code }}</span>
                                <span class="opacity-70">{{ $locale->label() !== $locale->code ? $locale->label() : '' }}</span>
                                @showroomCan('delete', $locale)
                                    <form method="POST" action="{{ route('atrium.showroom.locales.destroy', $locale) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-atrium::icon-button icon="trash" :label="__('showroom::showroom.delete')" variant="ghost" size="sm" type="submit" data-testid="delete-locale" />
                                    </form>
                                @endshowroomCan
                            </li>
                        @endforeach
                    </ul>
                @endif

                @showroomCan('create', \RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel::class)
                <form method="POST" action="{{ route('atrium.showroom.locales.store') }}" class="flex flex-wrap items-start gap-3">
                    @csrf
                    <x-atrium::form.input name="code" :label="__('showroom::showroom.code')" :hint="__('showroom::showroom.locale_code_hint')" required wrapper="w-44" />
                    <x-atrium::form.input :name="'labels['.$uiLocale.']'" :label="__('showroom::showroom.label_field', ['locale' => $uiLocale])" wrapper="w-56" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.add')" variant="primary" type="submit" data-testid="create-locale" />
                    </x-atrium::form.actions>
                </form>
                @endshowroomCan
            </x-atrium::card>
            @endshowroomCan

            @showroomCan('create', ChannelModel::class)
            <x-atrium::card data-testid="new-channel-card" :title="__('showroom::showroom.new_channel')">
                <form method="POST" action="{{ route('atrium.showroom.channels.store') }}" class="flex flex-col gap-4">
                    @csrf
                    <div class="flex flex-wrap items-start gap-3">
                        <x-atrium::form.input name="code" :label="__('showroom::showroom.code')" :hint="__('showroom::showroom.code_hint')" required wrapper="w-56" />
                        <x-atrium::form.input :name="'labels['.$uiLocale.']'" :label="__('showroom::showroom.label_field', ['locale' => $uiLocale])" wrapper="w-56" />
                    </div>
                    @include('showroom::ui.channels.fields', ['channel' => null])
                    <div>
                        <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.create')" variant="primary" type="submit" data-testid="create-channel" />
                    </div>
                </form>
            </x-atrium::card>
            @endshowroomCan

            @if ($channels->isEmpty())
                <x-atrium::empty-state :title="__('showroom::showroom.no_channels')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.code') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.locales') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.currencies') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.category_tree') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading></x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>

                    @foreach ($channels as $channel)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>
                                <a class="font-mono font-medium underline-offset-2 hover:underline" href="{{ route('atrium.showroom.channels.show', $channel) }}">{{ $channel->code }}</a>
                            </x-atrium::table.cell>
                            <x-atrium::table.cell class="font-mono text-xs">{{ $channel->locales->pluck('code')->join(', ') }}</x-atrium::table.cell>
                            <x-atrium::table.cell class="font-mono text-xs">{{ implode(', ', $channel->currencies ?? []) }}</x-atrium::table.cell>
                            <x-atrium::table.cell class="font-mono text-xs">{{ $channel->categoryTree?->code ?? __('showroom::showroom.none') }}</x-atrium::table.cell>
                            <x-atrium::table.cell><x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$channel" /></x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>
            @endif
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
