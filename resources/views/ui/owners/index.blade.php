@use(RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel)
@php($locale = app()->getLocale())

<x-atrium::layout :title="__('showroom::showroom.owners')">
    <x-atrium::page-header :title="__('showroom::showroom.owners')">
        <x-slot:actions>
            @showroomCan('viewAny', \RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel::class)
                <x-atrium::icon-button icon="identification" :label="__('showroom::showroom.owner_types')" variant="outline" :href="route('atrium.showroom.owner-types.index')" data-testid="owner-types" />
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

            @showroomCan('create', OwnerModel::class)
            <x-atrium::card data-testid="new-owner-card" :title="__('showroom::showroom.new_owner')">
                @if ($types === [])
                    <x-atrium::empty-state :title="__('showroom::showroom.no_owner_types')" />
                @else
                    <form method="POST" action="{{ route('atrium.showroom.owners.store') }}" class="flex flex-wrap items-start gap-3">
                        @csrf
                        <x-atrium::form.input name="code" :label="__('showroom::showroom.code')" required wrapper="w-48" />
                        <x-atrium::form.select name="type" :label="__('showroom::showroom.type')" :options="$types" required wrapper="w-44" />
                        <x-atrium::form.input name="parent" :label="__('showroom::showroom.parent')" :hint="__('showroom::showroom.parent_owner_hint')" wrapper="w-48" />
                        <x-atrium::form.input :name="'labels['.$locale.']'" :label="__('showroom::showroom.label_field', ['locale' => $locale])" wrapper="w-56" />
                        <x-atrium::form.actions>
                            <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.create')" variant="primary" type="submit" data-testid="create-owner" />
                        </x-atrium::form.actions>
                    </form>
                @endif
            </x-atrium::card>
            @endshowroomCan

            <x-atrium::card>
                <form method="GET" action="{{ route('atrium.showroom.owners.index') }}" class="flex flex-wrap items-start gap-3">
                    <x-atrium::form.input name="search" :label="__('showroom::showroom.search')" :value="$filters['search'] ?? null" wrapper="w-56" />
                    <x-atrium::form.select name="type" :label="__('showroom::showroom.type')" :placeholder="__('showroom::showroom.all_types')" :options="$types" :selected="$filters['type'] ?? null" wrapper="w-44" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="funnel" :label="__('showroom::showroom.filter')" variant="primary" type="submit" />
                        <x-atrium::icon-button icon="x-mark" :label="__('showroom::showroom.clear')" variant="ghost" :href="route('atrium.showroom.owners.index')" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>

            @if ($owners->isEmpty())
                <x-atrium::empty-state :title="__('showroom::showroom.no_owners')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.code') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.label_column') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.type') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.parent') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.children') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading></x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>

                    @foreach ($owners as $owner)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>
                                <a class="font-mono font-medium underline-offset-2 hover:underline" href="{{ route('atrium.showroom.owners.show', $owner) }}">{{ $owner->code }}</a>
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $owner->label() }}</x-atrium::table.cell>
                            <x-atrium::table.cell><x-atrium::badge>{{ $owner->type->code }}</x-atrium::badge></x-atrium::table.cell>
                            <x-atrium::table.cell class="font-mono text-xs">{{ $owner->parent?->code ?? __('showroom::showroom.none') }}</x-atrium::table.cell>
                            <x-atrium::table.cell class="tabular-nums">{{ $owner->children_count }}</x-atrium::table.cell>
                            <x-atrium::table.cell><x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$owner" /></x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>

                <x-atrium::pagination :paginator="$owners" />
            @endif
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
