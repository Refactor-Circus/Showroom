@use(RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel)

<x-atrium::layout :title="__('showroom::showroom.attributes')">
    <x-atrium::page-header :title="__('showroom::showroom.attributes')">
        <x-slot:actions>
            @showroomCan('create', AttributeModel::class)
                <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.new_attribute')" variant="primary" :href="route('atrium.showroom.attributes.create')" data-testid="new-attribute" />
            @endshowroomCan

            <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
        </x-slot:actions>
    </x-atrium::page-header>

    <x-atrium::screen-tabs :screen="$resolved" class="mt-4" />

    @if ($resolved->openRegisteredTab !== null)
        <x-atrium::screen-tab-panel :screen="$resolved" class="mt-5" />
    @else
        <x-atrium::screen-sections :screen="$resolved" slot="before" class="mt-5" />

        <div class="mt-5 flex flex-col gap-4">
            <x-atrium::flash />

            {{-- Type options come from the enum itself, so a new case can never
                 drift out of the filter list. --}}
            <x-atrium::card>
                <form method="GET" action="{{ route('atrium.showroom.attributes.index') }}" class="flex flex-wrap items-start gap-3">
                    <x-atrium::form.input name="search" :label="__('showroom::showroom.search')" :value="$filters['search'] ?? null" wrapper="w-56" />

                    <x-atrium::form.select
                        name="type"
                        :label="__('showroom::showroom.type')"
                        :placeholder="__('showroom::showroom.all_types')"
                        :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->value])"
                        :selected="$filters['type'] ?? null"
                        wrapper="w-44" />

                    <x-atrium::form.select
                        name="group"
                        :label="__('showroom::showroom.group')"
                        :placeholder="__('showroom::showroom.all_groups')"
                        :options="$groups"
                        :selected="$filters['group'] ?? null"
                        wrapper="w-48" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="funnel" :label="__('showroom::showroom.filter')" variant="primary" type="submit" data-testid="filter-attributes" />
                        <x-atrium::icon-button icon="x-mark" :label="__('showroom::showroom.clear')" variant="ghost" :href="route('atrium.showroom.attributes.index')" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>

            @if ($catalogAttributes->isEmpty())
                <x-atrium::empty-state :title="__('showroom::showroom.no_attributes')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.code') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.label_column') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.type') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.group') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.flags') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading></x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>

                    @foreach ($catalogAttributes as $attribute)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>
                                <a class="font-mono font-medium underline-offset-2 hover:underline"
                                   href="{{ route('atrium.showroom.attributes.show', $attribute) }}">{{ $attribute->code }}</a>
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $attribute->label() }}</x-atrium::table.cell>
                            <x-atrium::table.cell><x-atrium::badge>{{ $attribute->type->value }}</x-atrium::badge></x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $attribute->group?->label() ?? __('showroom::showroom.none') }}</x-atrium::table.cell>
                            <x-atrium::table.cell>
                                @if ($attribute->is_unique)<x-atrium::badge>{{ __('showroom::showroom.unique') }}</x-atrium::badge>@endif
                                @if ($attribute->is_localizable)<x-atrium::badge>{{ __('showroom::showroom.localizable') }}</x-atrium::badge>@endif
                                @if ($attribute->is_scopable)<x-atrium::badge>{{ __('showroom::showroom.scopable') }}</x-atrium::badge>@endif
                            </x-atrium::table.cell>
                            <x-atrium::table.cell><x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$attribute" /></x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>

                <x-atrium::pagination :paginator="$catalogAttributes" />
            @endif
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
