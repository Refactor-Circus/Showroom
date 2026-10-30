@php($locale = app()->getLocale())

<x-atrium::layout :title="$owner->label()">
    <x-atrium::page-header :title="$owner->label()" :description="$owner->code.' · '.$owner->type->code">
        <x-slot:actions>
            @showroomCan('viewAny', \RefactorCircus\Showroom\Domains\Product\Models\ProductModel::class)
                <x-atrium::icon-button icon="cube" :label="__('showroom::showroom.products')" variant="outline" :href="route('atrium.showroom.products.index', ['owner' => $owner->code])" data-testid="owner-products" />
            @endshowroomCan

            @showroomCan('delete', $owner)
                <form method="POST" action="{{ route('atrium.showroom.owners.destroy', $owner) }}">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="trash" :label="__('showroom::showroom.delete')" variant="danger" type="submit" data-testid="delete-owner" />
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

            <nav aria-label="{{ __('showroom::showroom.chain') }}" class="flex flex-wrap items-center gap-1 text-sm" data-testid="owner-chain">
                @foreach ($owner->getRelation('chain') as $link)
                    @unless ($loop->first)<span class="opacity-50">›</span>@endunless
                    @if ($link->is($owner))
                        <span class="font-medium">{{ $link->label() }}</span>
                    @else
                        <a class="underline-offset-2 hover:underline" href="{{ route('atrium.showroom.owners.show', $link) }}">{{ $link->label() }}</a>
                    @endif
                    <span class="font-mono text-xs opacity-60">{{ $link->type->code }}</span>
                @endforeach
            </nav>

            @showroomCan('update', $owner)
            <x-atrium::card data-testid="owner-details-card" :title="__('showroom::showroom.details')">
                <form method="POST" action="{{ route('atrium.showroom.owners.update', $owner) }}" class="flex flex-wrap items-start gap-3">
                    @csrf
                    @method('PATCH')
                    <x-atrium::form.input :name="'labels['.$locale.']'" :label="__('showroom::showroom.label_field', ['locale' => $locale])" :value="$owner->labels[$locale] ?? null" wrapper="w-64" />
                    <x-atrium::form.input name="parent" :label="__('showroom::showroom.parent')" :hint="__('showroom::showroom.move_hint')" :value="$owner->parent?->code" wrapper="w-56" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="check" :label="__('showroom::showroom.save')" variant="primary" type="submit" data-testid="save-owner" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>
            @endshowroomCan

            <x-atrium::card :title="__('showroom::showroom.children')">
                @if ($owner->children->isNotEmpty())
                    <ul class="mb-4 flex flex-wrap gap-3">
                        @foreach ($owner->children as $child)
                            <li class="text-sm">
                                <a class="font-mono underline-offset-2 hover:underline" href="{{ route('atrium.showroom.owners.show', $child) }}">{{ $child->code }}</a>
                                <span class="text-xs opacity-60">{{ $child->type->code }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @showroomCan('create', \RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel::class)
                <form method="POST" action="{{ route('atrium.showroom.owners.store') }}" class="flex flex-wrap items-start gap-3">
                    @csrf
                    <input type="hidden" name="parent" value="{{ $owner->code }}">
                    <x-atrium::form.input name="code" :label="__('showroom::showroom.code')" required wrapper="w-48" />
                    <x-atrium::form.select name="type" :label="__('showroom::showroom.type')" :options="$types" required wrapper="w-44" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.add_child')" variant="primary" type="submit" data-testid="add-child-owner" />
                    </x-atrium::form.actions>
                </form>
                @endshowroomCan
            </x-atrium::card>

            @include('showroom::ui.partials.assets', [
                'own' => $owner->assets,
                'inheritedAssets' => collect(),
                'linkType' => 'owner',
                'linkTarget' => $owner->code,
            ])

            <p class="text-sm text-on-surface dark:text-on-surface-dark">
                {{ __('showroom::showroom.owned_counts', ['products' => $owner->products_count, 'models' => $owner->product_models_count]) }}
            </p>
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="aside" class="mt-5" />

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
