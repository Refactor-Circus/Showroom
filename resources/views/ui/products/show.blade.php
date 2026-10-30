@use(RefactorCircus\Showroom\Atrium\Badges)
@use(RefactorCircus\Showroom\Atrium\ScreenAccess)

@php($canUpdate = ScreenAccess::allows('update', $product))

<x-atrium::layout :title="$product->identifier">
    <x-atrium::page-header :title="$product->identifier" :description="$product->family?->label()">
        <x-slot:actions>
            <x-atrium::status-dot :variant="Badges::forEnabled($product->enabled)" :label="$product->enabled ? __('showroom::showroom.enabled') : __('showroom::showroom.disabled')" data-status="{{ $product->enabled ? 'enabled' : 'disabled' }}" data-testid="product-enabled" />

            @if ($product->parent)
                @showroomCan('view', $product->parent)
                    <x-atrium::icon-button icon="arrow-left" :label="$product->parent->code" variant="outline" :href="route('atrium.showroom.product-models.show', $product->parent)" data-testid="parent-model" />
                @endshowroomCan
            @endif

            @showroomCan('delete', $product)
                <form method="POST" action="{{ route('atrium.showroom.products.destroy', $product) }}">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="trash" :label="__('showroom::showroom.delete')" variant="danger" type="submit" data-testid="delete-product" />
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

            @include('showroom::ui.products.workflow')

            @include('showroom::ui.partials.slot-picker')

            {{-- Shown to those who may only view it, read-only and without save. --}}
            <form method="POST" action="{{ route('atrium.showroom.products.update', $product) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="add" value="{{ request('add') }}">

                <fieldset class="flex min-w-0 flex-col gap-5" @disabled(! $canUpdate)>

                    <x-atrium::card :title="__('showroom::showroom.details')">
                        <div class="flex flex-wrap items-start gap-4">
                            @unless ($product->isVariant())
                                <x-atrium::form.select
                                    name="family"
                                    :label="__('showroom::showroom.family')"
                                    :placeholder="__('showroom::showroom.no_family')"
                                    :options="$families"
                                    :selected="$product->family?->code"
                                    wrapper="w-56" />
                            @endunless

                            @if ($product->isVariant())
                                <div class="text-sm">
                                    <p class="font-medium">{{ __('showroom::showroom.inherited_owner') }}</p>
                                    <p class="font-mono text-xs">{{ $product->effectiveOwner()?->code ?? __('showroom::showroom.none') }}</p>
                                </div>
                            @else
                                <x-atrium::form.input name="owner" :label="__('showroom::showroom.owner')" :hint="__('showroom::showroom.owner_hint')" :value="$product->owner?->code" wrapper="w-56" />
                            @endif
                            <x-atrium::form.actions>
                                <input type="hidden" name="enabled" value="0">
                                <x-atrium::form.checkbox name="enabled" :label="__('showroom::showroom.enabled')" :checked="$product->enabled" wrapper="h-9 w-auto justify-center" />
                            </x-atrium::form.actions>
                        </div>
                    </x-atrium::card>

                    <x-atrium::card :title="__('showroom::showroom.categories')">
                        @include('showroom::ui.partials.categories', [
                            'record' => $product,
                            'inheritedCategories' => $product->parent?->allCategories() ?? collect(),
                        ])
                    </x-atrium::card>

                    <x-atrium::card :title="__('showroom::showroom.values')">
                        @include('showroom::ui.partials.values', ['values' => $product->ownValues()])
                    </x-atrium::card>

                    @if ($canUpdate)
                        <div>
                            <x-atrium::icon-button icon="check" :label="__('showroom::showroom.save')" variant="primary" type="submit" data-testid="save-product" />
                        </div>
                    @endif
                </fieldset>
            </form>

            @if ($addable !== [] && $canUpdate)
                <x-atrium::card>
                    <form method="GET" action="{{ route('atrium.showroom.products.show', $product) }}" class="flex flex-wrap items-start gap-3">
                        <x-atrium::form.select name="add" :label="__('showroom::showroom.add_attribute')" :placeholder="__('showroom::showroom.choose_attribute')" :options="$addable" wrapper="w-72" />
                        <x-atrium::form.actions>
                            <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.add')" variant="outline" type="submit" />
                        </x-atrium::form.actions>
                    </form>
                </x-atrium::card>
            @endif

            @include('showroom::ui.partials.assets', [
                'own' => $product->assets,
                'inheritedAssets' => $product->parent?->allAssets() ?? collect(),
                'linkType' => 'product',
                'linkTarget' => $product->identifier,
            ])

            @include('showroom::ui.partials.associations', ['record' => $product, 'sourceKind' => 'product', 'sourceKey' => $product->identifier])

            @include('showroom::ui.partials.inherited')
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="aside" class="mt-5" />

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
