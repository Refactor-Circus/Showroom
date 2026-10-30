@use(RefactorCircus\Showroom\Domains\Category\Models\CategoryModel)
@php($locale = app()->getLocale())

<x-atrium::layout :title="__('showroom::showroom.categories')">
    <x-atrium::page-header :title="__('showroom::showroom.categories')" :description="__('showroom::showroom.categories_description')">
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

            @showroomCan('create', CategoryModel::class)
            <x-atrium::card data-testid="new-tree-card" :title="__('showroom::showroom.new_tree')">
                <form method="POST" action="{{ route('atrium.showroom.categories.store') }}" class="flex flex-wrap items-start gap-3">
                    @csrf
                    <x-atrium::form.input name="code" :label="__('showroom::showroom.code')" :hint="__('showroom::showroom.code_hint')" required wrapper="w-56" />
                    <x-atrium::form.input :name="'labels['.$locale.']'" :label="__('showroom::showroom.label_field', ['locale' => $locale])" wrapper="w-56" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.create')" variant="primary" type="submit" data-testid="create-tree" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>
            @endshowroomCan

            @if ($trees->isEmpty())
                <x-atrium::empty-state :title="__('showroom::showroom.no_trees')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.code') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.label_column') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.children') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading></x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>

                    @foreach ($trees as $tree)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>
                                <a class="font-mono font-medium underline-offset-2 hover:underline" href="{{ route('atrium.showroom.categories.show', $tree) }}">{{ $tree->code }}</a>
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $tree->label() }}</x-atrium::table.cell>
                            <x-atrium::table.cell class="tabular-nums">{{ $tree->children_count }}</x-atrium::table.cell>
                            <x-atrium::table.cell><x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$tree" /></x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>

                <x-atrium::pagination :paginator="$trees" />
            @endif
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
