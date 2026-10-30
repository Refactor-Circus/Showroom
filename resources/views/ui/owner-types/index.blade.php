@use(RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel)
@php($locale = app()->getLocale())

<x-atrium::layout :title="__('showroom::showroom.owner_types')">
    <x-atrium::page-header :title="__('showroom::showroom.owner_types')" :description="__('showroom::showroom.owner_types_description')">
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

            @showroomCan('create', OwnerTypeModel::class)
            <x-atrium::card data-testid="new-owner-type-card" :title="__('showroom::showroom.new_owner_type')">
                <form method="POST" action="{{ route('atrium.showroom.owner-types.store') }}" class="flex flex-col gap-4">
                    @csrf
                    <div class="flex flex-wrap items-start gap-3">
                        <x-atrium::form.input name="code" :label="__('showroom::showroom.code')" :hint="__('showroom::showroom.code_hint')" required wrapper="w-56" />
                        <x-atrium::form.input :name="'labels['.$locale.']'" :label="__('showroom::showroom.label_field', ['locale' => $locale])" wrapper="w-56" />
                    </div>

                    @include('showroom::ui.owner-types.rules', ['type' => null])

                    <div>
                        <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.create')" variant="primary" type="submit" data-testid="create-owner-type" />
                    </div>
                </form>
            </x-atrium::card>
            @endshowroomCan

            @if ($types->isEmpty())
                <x-atrium::empty-state :title="__('showroom::showroom.no_owner_types')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.code') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.label_column') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.parent_types') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.owners') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading></x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>

                    @foreach ($types as $type)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>
                                <a class="font-mono font-medium underline-offset-2 hover:underline" href="{{ route('atrium.showroom.owner-types.show', $type) }}">{{ $type->code }}</a>
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $type->label() }}</x-atrium::table.cell>
                            <x-atrium::table.cell class="font-mono text-xs">
                                {{ $type->restricts_parents ? ($type->parentTypes->pluck('code')->join(', ') ?: __('showroom::showroom.none')) : __('showroom::showroom.any') }}
                            </x-atrium::table.cell>
                            <x-atrium::table.cell class="tabular-nums">{{ $type->owners_count }}</x-atrium::table.cell>
                            <x-atrium::table.cell><x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$type" /></x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>

                <x-atrium::pagination :paginator="$types" />
            @endif
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
