@use(RefactorCircus\Showroom\Atrium\Badges)
@use(RefactorCircus\Showroom\Domains\Product\Models\ProductModel)

<x-atrium::layout :title="__('showroom::showroom.products')">
    <x-atrium::page-header :title="__('showroom::showroom.products')">
        <x-slot:actions>
            @showroomCan('create', ProductModel::class)
                <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.new_product')" variant="primary" :href="route('atrium.showroom.products.create')" data-testid="new-product" />
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

            <x-atrium::card>
                <form method="GET" action="{{ route('atrium.showroom.products.index') }}" class="flex flex-wrap items-start gap-3">
                    <x-atrium::form.input name="search" :label="__('showroom::showroom.search')" :value="$filters['search'] ?? null" wrapper="w-64" />

                    <x-atrium::form.select
                        name="family"
                        :label="__('showroom::showroom.family')"
                        :placeholder="__('showroom::showroom.all_families')"
                        :options="$families"
                        :selected="$filters['family'] ?? null"
                        wrapper="w-48" />

                    <x-atrium::form.input name="category" :label="__('showroom::showroom.category')" :value="$filters['category'] ?? null" wrapper="w-44" />

                    <x-atrium::form.input name="owner" :label="__('showroom::showroom.owner')" :value="$filters['owner'] ?? null" wrapper="w-44" />

                    <x-atrium::form.select
                        name="status"
                        :label="__('showroom::showroom.workflow')"
                        :placeholder="__('showroom::showroom.all_statuses')"
                        :options="collect(\RefactorCircus\Showroom\Domains\Product\Enums\ProductStatus::cases())->mapWithKeys(fn ($status) => [$status->value => __('showroom::showroom.status_'.$status->value)])"
                        :selected="$filters['status'] ?? null"
                        wrapper="w-40" />

                    <x-atrium::form.select
                        name="enabled"
                        :label="__('showroom::showroom.status')"
                        :placeholder="__('showroom::showroom.all_statuses')"
                        :options="['1' => __('showroom::showroom.enabled'), '0' => __('showroom::showroom.disabled')]"
                        :selected="isset($filters['enabled']) ? (string) (int) $filters['enabled'] : null"
                        wrapper="w-40" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="funnel" :label="__('showroom::showroom.filter')" variant="primary" type="submit" data-testid="filter-products" />
                        <x-atrium::icon-button icon="x-mark" :label="__('showroom::showroom.clear')" variant="ghost" :href="route('atrium.showroom.products.index')" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>

            @if ($error)
                <x-atrium::alert variant="danger">{{ $error }}</x-atrium::alert>
            @elseif ($products->isEmpty())
                <x-atrium::empty-state :title="__('showroom::showroom.no_products')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.identifier') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.label_column') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.family') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.parent') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.status') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.workflow') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('showroom::showroom.updated') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading></x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>

                    @foreach ($products as $product)
                        @php($labelCode = $product->family?->labelAttribute?->code)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>
                                <a class="font-mono font-medium underline-offset-2 hover:underline"
                                   href="{{ route('atrium.showroom.products.show', $product) }}">{{ $product->identifier }}</a>
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $labelCode ? ($product->value($labelCode) ?? __('showroom::showroom.none')) : __('showroom::showroom.none') }}</x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $product->family?->label() ?? __('showroom::showroom.none') }}</x-atrium::table.cell>
                            <x-atrium::table.cell class="font-mono text-xs">{{ $product->parent?->code ?? __('showroom::showroom.none') }}</x-atrium::table.cell>
                            <x-atrium::table.cell>
                                <x-atrium::status-dot :variant="Badges::forEnabled($product->enabled)" :label="$product->enabled ? __('showroom::showroom.enabled') : __('showroom::showroom.disabled')" data-status="{{ $product->enabled ? 'enabled' : 'disabled' }}" />
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>
                                <span class="flex items-center gap-2">
                                    <x-atrium::status-dot :variant="Badges::forStatus($product->status)" :label="__('showroom::showroom.status_'.$product->status->value)" data-status="{{ $product->status->value }}" />
                                    @if ($product->published_version)<x-atrium::badge>v{{ $product->published_version }}</x-atrium::badge>@endif
                                </span>
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $product->updated_at?->diffForHumans() }}</x-atrium::table.cell>
                            <x-atrium::table.cell><x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$product" /></x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>

                <x-atrium::pagination :paginator="$products" />
            @endif
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
