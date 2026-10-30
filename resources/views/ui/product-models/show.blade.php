@use(RefactorCircus\Showroom\Atrium\ScreenAccess)
@use(RefactorCircus\Showroom\Domains\Product\Models\ProductModel)
@use(RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel)

@php($canUpdate = ScreenAccess::allows('update', $model))

<x-atrium::layout :title="$model->code">
    <x-atrium::page-header :title="$model->code" :description="$model->familyVariant->code.' · '.__('showroom::showroom.level', ['level' => $model->level()])">
        <x-slot:actions>
            @if ($model->parent)
                @showroomCan('view', $model->parent)
                    <x-atrium::icon-button icon="arrow-left" :label="$model->parent->code" variant="outline" :href="route('atrium.showroom.product-models.show', $model->parent)" data-testid="parent-model" />
                @endshowroomCan
            @endif

            @showroomCan('delete', $model)
                <form method="POST" action="{{ route('atrium.showroom.product-models.destroy', $model) }}">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="trash" :label="__('showroom::showroom.delete')" variant="danger" type="submit" data-testid="delete-product-model" />
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

            @include('showroom::ui.partials.slot-picker')

            {{-- Shown to those who may only view it, read-only and without save. --}}
            <form method="POST" action="{{ route('atrium.showroom.product-models.update', $model) }}">
                @csrf
                @method('PATCH')

                <fieldset class="flex min-w-0 flex-col gap-5" @disabled(! $canUpdate)>

                    @if ($model->level() === 0)
                        <x-atrium::card :title="__('showroom::showroom.details')">
                            <x-atrium::form.input name="owner" :label="__('showroom::showroom.owner')" :hint="__('showroom::showroom.owner_hint')" :value="$model->owner?->code" wrapper="w-56" />
                        </x-atrium::card>
                    @endif

                    <x-atrium::card :title="__('showroom::showroom.categories')">
                        @include('showroom::ui.partials.categories', [
                            'record' => $model,
                            'inheritedCategories' => $model->parent?->allCategories() ?? collect(),
                        ])
                    </x-atrium::card>

                    <x-atrium::card :title="__('showroom::showroom.values')">
                        @include('showroom::ui.partials.values', ['values' => $model->ownValues()])
                    </x-atrium::card>

                    @if ($canUpdate)
                        <div>
                            <x-atrium::icon-button icon="check" :label="__('showroom::showroom.save')" variant="primary" type="submit" data-testid="save-product-model" />
                        </div>
                    @endif
                </fieldset>
            </form>

            @include('showroom::ui.partials.assets', [
                'own' => $model->assets,
                'inheritedAssets' => $model->parent?->allAssets() ?? collect(),
                'linkType' => 'product_model',
                'linkTarget' => $model->code,
            ])

            @include('showroom::ui.partials.associations', ['record' => $model, 'sourceKind' => 'product_model', 'sourceKey' => $model->code])

            @include('showroom::ui.partials.inherited')

            @if ($model->holdsProducts())
                <x-atrium::card :title="__('showroom::showroom.variant_products')">
                    @if ($model->products->isNotEmpty())
                        <ul class="mb-4 flex flex-wrap gap-2">
                            @foreach ($model->products as $product)
                                <li><a class="font-mono text-sm underline-offset-2 hover:underline" href="{{ route('atrium.showroom.products.show', $product) }}">{{ $product->identifier }}</a></li>
                            @endforeach
                        </ul>
                    @endif

                    @showroomCan('create', ProductModel::class)
                    <form method="POST" action="{{ route('atrium.showroom.products.store') }}" class="flex flex-wrap items-start gap-3">
                        @csrf
                        <input type="hidden" name="parent" value="{{ $model->code }}">
                        <x-atrium::form.input name="identifier" :label="__('showroom::showroom.identifier')" required wrapper="w-56" />
                        @include('showroom::ui.partials.axes', ['axes' => $model->familyVariant->axesAt($model->familyVariant->levels)])
                        <x-atrium::form.actions>
                            <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.add_variant')" variant="primary" type="submit" data-testid="create-variant" />
                        </x-atrium::form.actions>
                    </form>
                    @endshowroomCan
                </x-atrium::card>
            @else
                <x-atrium::card :title="__('showroom::showroom.sub_models')">
                    @if ($model->children->isNotEmpty())
                        <ul class="mb-4 flex flex-wrap gap-2">
                            @foreach ($model->children as $child)
                                <li><a class="font-mono text-sm underline-offset-2 hover:underline" href="{{ route('atrium.showroom.product-models.show', $child) }}">{{ $child->code }}</a></li>
                            @endforeach
                        </ul>
                    @endif

                    @showroomCan('create', ProductModelModel::class)
                    <form method="POST" action="{{ route('atrium.showroom.product-models.store') }}" class="flex flex-wrap items-start gap-3">
                        @csrf
                        <input type="hidden" name="parent" value="{{ $model->code }}">
                        <x-atrium::form.input name="code" :label="__('showroom::showroom.code')" required wrapper="w-56" />
                        @include('showroom::ui.partials.axes', ['axes' => $model->familyVariant->axesAt(1)])
                        <x-atrium::form.actions>
                            <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.add_sub_model')" variant="primary" type="submit" data-testid="create-sub-model" />
                        </x-atrium::form.actions>
                    </form>
                    @endshowroomCan
                </x-atrium::card>
            @endif
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="aside" class="mt-5" />

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
