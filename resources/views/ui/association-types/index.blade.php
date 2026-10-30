@use(RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel)
@php($locale = app()->getLocale())

<x-atrium::layout :title="__('showroom::showroom.association_types')">
    <x-atrium::page-header :title="__('showroom::showroom.association_types')" :description="__('showroom::showroom.association_types_description')">
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

            @showroomCan('create', AssociationTypeModel::class)
            <x-atrium::card data-testid="new-association-type-card" :title="__('showroom::showroom.new_association_type')">
                <form method="POST" action="{{ route('atrium.showroom.association-types.store') }}" class="flex flex-wrap items-start gap-3">
                    @csrf
                    <x-atrium::form.input name="code" :label="__('showroom::showroom.code')" :hint="__('showroom::showroom.code_hint')" required wrapper="w-56" />
                    <x-atrium::form.input :name="'labels['.$locale.']'" :label="__('showroom::showroom.label_field', ['locale' => $locale])" wrapper="w-56" />
                    <x-atrium::form.actions>
                        <input type="hidden" name="is_two_way" value="0">
                        <x-atrium::form.checkbox name="is_two_way" :label="__('showroom::showroom.two_way')" wrapper="h-9 w-auto justify-center" />
                        <input type="hidden" name="is_quantified" value="0">
                        <x-atrium::form.checkbox name="is_quantified" :label="__('showroom::showroom.quantified')" wrapper="h-9 w-auto justify-center" />
                        <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.create')" variant="primary" type="submit" data-testid="create-association-type" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>
            @endshowroomCan

            @if ($types->isEmpty())
                <x-atrium::empty-state :title="__('showroom::showroom.no_association_types')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.code') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.label_column') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.flags') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.associations') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading></x-atrium::table.cell>
                            <x-atrium::table.cell heading></x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>

                    @foreach ($types as $type)
                        <x-atrium::table.row>
                            <x-atrium::table.cell class="font-mono">{{ $type->code }}</x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $type->label() }}</x-atrium::table.cell>
                            <x-atrium::table.cell>
                                @if ($type->is_two_way)<x-atrium::badge>{{ __('showroom::showroom.two_way') }}</x-atrium::badge>@endif
                                @if ($type->is_quantified)<x-atrium::badge>{{ __('showroom::showroom.quantified') }}</x-atrium::badge>@endif
                            </x-atrium::table.cell>
                            <x-atrium::table.cell class="tabular-nums">{{ $type->associations_count }}</x-atrium::table.cell>
                            <x-atrium::table.cell>
                                @showroomCan('delete', $type)
                                    <form method="POST" action="{{ route('atrium.showroom.association-types.destroy', $type) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-atrium::icon-button icon="trash" :label="__('showroom::showroom.delete')" variant="ghost" size="sm" type="submit" data-testid="delete-association-type" />
                                    </form>
                                @endshowroomCan
                            </x-atrium::table.cell>
                            <x-atrium::table.cell><x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$type" /></x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>
            @endif
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
