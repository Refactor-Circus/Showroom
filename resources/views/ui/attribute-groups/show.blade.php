@php($locale = app()->getLocale())

<x-atrium::layout :title="$group->label()">
    <x-atrium::page-header :title="$group->label()" :description="$group->code">
        <x-slot:actions>
            @showroomCan('delete', $group)
                <form method="POST" action="{{ route('atrium.showroom.attribute-groups.destroy', $group) }}">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="trash" :label="__('showroom::showroom.delete')" variant="danger" type="submit" data-testid="delete-group" />
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

            @showroomCan('update', $group)
            <x-atrium::card data-testid="group-details-card" :title="__('showroom::showroom.details')">
                <form method="POST" action="{{ route('atrium.showroom.attribute-groups.update', $group) }}" class="flex flex-wrap items-start gap-3">
                    @csrf
                    @method('PATCH')
                    <x-atrium::form.input
                        :name="'labels['.$locale.']'"
                        :label="__('showroom::showroom.label_field', ['locale' => $locale])"
                        :value="$group->labels[$locale] ?? null"
                        wrapper="w-64" />
                    <x-atrium::form.input name="sort_order" type="number" min="0" :label="__('showroom::showroom.sort_order')" :value="$group->sort_order" wrapper="w-28" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="check" :label="__('showroom::showroom.save')" variant="primary" type="submit" data-testid="save-group" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>
            @endshowroomCan

            <x-atrium::card :title="__('showroom::showroom.group_attributes')">
                @if ($group->groupedAttributes->isEmpty())
                    <x-atrium::empty-state :title="__('showroom::showroom.no_group_attributes')" />
                @else
                    <x-atrium::table compact>
                        <x-slot:head>
                            <x-atrium::table.row>
                                <x-atrium::table.cell heading>{{ __('showroom::showroom.code') }}</x-atrium::table.cell>
                                <x-atrium::table.cell heading>{{ __('showroom::showroom.label_column') }}</x-atrium::table.cell>
                                <x-atrium::table.cell heading>{{ __('showroom::showroom.type') }}</x-atrium::table.cell>
                            </x-atrium::table.row>
                        </x-slot:head>

                        @foreach ($group->groupedAttributes as $attribute)
                            <x-atrium::table.row>
                                <x-atrium::table.cell>
                                    <a class="font-mono underline-offset-2 hover:underline"
                                       href="{{ route('atrium.showroom.attributes.show', $attribute) }}">{{ $attribute->code }}</a>
                                </x-atrium::table.cell>
                                <x-atrium::table.cell>{{ $attribute->label() }}</x-atrium::table.cell>
                                <x-atrium::table.cell><x-atrium::badge>{{ $attribute->type->value }}</x-atrium::badge></x-atrium::table.cell>
                            </x-atrium::table.row>
                        @endforeach
                    </x-atrium::table>
                @endif
            </x-atrium::card>
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="aside" class="mt-5" />

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
