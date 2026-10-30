@php($locale = app()->getLocale())

<x-atrium::layout :title="$category->label()">
    <x-atrium::page-header :title="$category->label()" :description="$category->code">
        <x-slot:actions>
            @showroomCan('viewAny', \RefactorCircus\Showroom\Domains\Product\Models\ProductModel::class)
                <x-atrium::icon-button icon="cube" :label="__('showroom::showroom.products')" variant="outline" :href="route('atrium.showroom.products.index', ['category' => $category->code])" data-testid="category-products" />
            @endshowroomCan

            @showroomCan('delete', $category)
                <form method="POST" action="{{ route('atrium.showroom.categories.destroy', $category) }}">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="trash" :label="__('showroom::showroom.delete')" variant="danger" type="submit" data-testid="delete-category" />
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

            <nav aria-label="{{ __('showroom::showroom.chain') }}" class="flex flex-wrap items-center gap-1 text-sm" data-testid="category-chain">
                <a class="underline-offset-2 hover:underline" href="{{ route('atrium.showroom.categories.index') }}">{{ __('showroom::showroom.categories') }}</a>
                @foreach ($category->getRelation('chain') as $link)
                    <span class="opacity-50">›</span>
                    @if ($link->is($category))
                        <span class="font-medium">{{ $link->label() }}</span>
                    @else
                        <a class="underline-offset-2 hover:underline" href="{{ route('atrium.showroom.categories.show', $link) }}">{{ $link->label() }}</a>
                    @endif
                @endforeach
            </nav>

            <x-atrium::card :title="__('showroom::showroom.branch')">
                @if ($branch->isEmpty())
                    <x-atrium::empty-state :title="__('showroom::showroom.no_subcategories')" />
                @else
                    @include('showroom::ui.categories.branch', ['parentId' => $category->id, 'nested' => false])
                @endif

                @showroomCan('create', \RefactorCircus\Showroom\Domains\Category\Models\CategoryModel::class)
                <form method="POST" action="{{ route('atrium.showroom.categories.store') }}" class="mt-4 flex flex-wrap items-start gap-3">
                    @csrf
                    <input type="hidden" name="parent" value="{{ $category->code }}">
                    <x-atrium::form.input name="code" :label="__('showroom::showroom.code')" required wrapper="w-48" />
                    <x-atrium::form.input :name="'labels['.$locale.']'" :label="__('showroom::showroom.label_field', ['locale' => $locale])" wrapper="w-56" />
                    <x-atrium::form.input name="sort_order" type="number" min="0" :label="__('showroom::showroom.sort_order')" value="0" wrapper="w-28" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.add_child')" variant="primary" type="submit" data-testid="add-subcategory" />
                    </x-atrium::form.actions>
                </form>
                @endshowroomCan
            </x-atrium::card>

            @showroomCan('update', $category)
            <x-atrium::card data-testid="category-details-card" :title="__('showroom::showroom.details')">
                <form method="POST" action="{{ route('atrium.showroom.categories.update', $category) }}" class="flex flex-wrap items-start gap-3">
                    @csrf
                    @method('PATCH')
                    <x-atrium::form.input :name="'labels['.$locale.']'" :label="__('showroom::showroom.label_field', ['locale' => $locale])" :value="$category->labels[$locale] ?? null" wrapper="w-64" />
                    <x-atrium::form.input name="sort_order" type="number" min="0" :label="__('showroom::showroom.sort_order')" :value="$category->sort_order" wrapper="w-28" />
                    <x-atrium::form.input name="parent" :label="__('showroom::showroom.parent')" :hint="__('showroom::showroom.category_move_hint')" :value="$category->parent?->code" wrapper="w-56" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="check" :label="__('showroom::showroom.save')" variant="primary" type="submit" data-testid="save-category" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>
            @endshowroomCan

            <p class="text-sm text-on-surface dark:text-on-surface-dark">
                {{ __('showroom::showroom.filed_counts', ['products' => $category->products_count, 'models' => $category->product_models_count]) }}
            </p>
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="aside" class="mt-5" />

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
