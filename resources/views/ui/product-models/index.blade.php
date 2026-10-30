@use(RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel)

<x-atrium::layout :title="__('showroom::showroom.product_models')">
    <x-atrium::page-header :title="__('showroom::showroom.product_models')">
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

            @showroomCan('create', ProductModelModel::class)
            <x-atrium::card :title="__('showroom::showroom.new_product_model')" data-testid="new-product-model-card">
                <form method="POST" action="{{ route('atrium.showroom.product-models.store') }}" class="flex flex-wrap items-start gap-3">
                    @csrf
                    <x-atrium::form.input name="code" :label="__('showroom::showroom.code')" required wrapper="w-56" />
                    <x-atrium::form.select name="family_variant" :label="__('showroom::showroom.family_variant')" :placeholder="__('showroom::showroom.choose_family_variant')" :options="$variants" required wrapper="w-64" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.create')" variant="primary" type="submit" data-testid="create-product-model" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>
            @endshowroomCan

            <x-atrium::card>
                <form method="GET" action="{{ route('atrium.showroom.product-models.index') }}" class="flex flex-wrap items-start gap-3">
                    <x-atrium::form.input name="search" :label="__('showroom::showroom.search')" :value="$filters['search'] ?? null" wrapper="w-56" />
                    <x-atrium::form.select name="family_variant" :label="__('showroom::showroom.family_variant')" :placeholder="__('showroom::showroom.all_family_variants')" :options="$variants" :selected="$filters['family_variant'] ?? null" wrapper="w-64" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="funnel" :label="__('showroom::showroom.filter')" variant="primary" type="submit" />
                        <x-atrium::icon-button icon="x-mark" :label="__('showroom::showroom.clear')" variant="ghost" :href="route('atrium.showroom.product-models.index')" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>

            @if ($models->isEmpty())
                <x-atrium::empty-state :title="__('showroom::showroom.no_product_models')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.code') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.family_variant') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.updated') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading></x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>

                    @foreach ($models as $model)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>
                                <a class="font-mono font-medium underline-offset-2 hover:underline" href="{{ route('atrium.showroom.product-models.show', $model) }}">{{ $model->code }}</a>
                            </x-atrium::table.cell>
                            <x-atrium::table.cell class="font-mono text-xs">{{ $model->familyVariant->code }}</x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $model->updated_at?->diffForHumans() }}</x-atrium::table.cell>
                            <x-atrium::table.cell><x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$model" /></x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>

                <x-atrium::pagination :paginator="$models" />
            @endif
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
