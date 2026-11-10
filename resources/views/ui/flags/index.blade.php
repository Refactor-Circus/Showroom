@use(RefactorCircus\Showroom\Domains\Flag\Models\FlagModel)
@php($locale = app()->getLocale())

<x-atrium::layout :title="__('showroom::showroom.flag_definitions')">
    <x-atrium::page-header :title="__('showroom::showroom.flag_definitions')" :description="__('showroom::showroom.flags_description')">
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

            @showroomCan('create', FlagModel::class)
            <x-atrium::card data-testid="new-flag-card" :title="__('showroom::showroom.new_flag')">
                <form method="POST" action="{{ route('atrium.showroom.flags.store') }}" class="flex flex-col gap-4">
                    @csrf
                    <div class="flex flex-wrap items-start gap-3">
                        <x-atrium::form.input name="code" :label="__('showroom::showroom.code')" :hint="__('showroom::showroom.code_hint')" required wrapper="w-56" />
                        <x-atrium::form.input :name="'labels['.$locale.']'" :label="__('showroom::showroom.label_field', ['locale' => $locale])" wrapper="w-56" />
                    </div>

                    @include('showroom::ui.flags.fields', ['flag' => null])

                    <div>
                        <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.create')" variant="primary" type="submit" data-testid="create-flag" />
                    </div>
                </form>
            </x-atrium::card>
            @endshowroomCan

            @if ($flags->isEmpty())
                <x-atrium::empty-state :title="__('showroom::showroom.no_flags')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.code') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.label_column') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.applies_to') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.sort_order') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading></x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>

                    @foreach ($flags as $flag)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>
                                <a class="font-mono font-medium underline-offset-2 hover:underline" href="{{ route('atrium.showroom.flags.show', $flag) }}">{{ $flag->code }}</a>
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $flag->label() }}</x-atrium::table.cell>
                            <x-atrium::table.cell>
                                @foreach ($flag->applies_to as $subject)
                                    <x-atrium::badge>{{ __('showroom::showroom.flag_subject_'.$subject) }}</x-atrium::badge>
                                @endforeach
                            </x-atrium::table.cell>
                            <x-atrium::table.cell class="tabular-nums">{{ $flag->sort_order }}</x-atrium::table.cell>
                            <x-atrium::table.cell><x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$flag" /></x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>

                <x-atrium::pagination :paginator="$flags" />
            @endif
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
