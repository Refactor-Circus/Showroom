@php($locale = app()->getLocale())

<x-atrium::layout :title="$type->label()">
    <x-atrium::page-header :title="$type->label()" :description="$type->code">
        <x-slot:actions>
            @showroomCan('viewAny', \RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel::class)
                <x-atrium::icon-button icon="building-storefront" :label="__('showroom::showroom.owners').' ('.$type->owners_count.')'" variant="outline" :href="route('atrium.showroom.owners.index', ['type' => $type->code])" data-testid="type-owners" />
            @endshowroomCan

            @showroomCan('delete', $type)
                <form method="POST" action="{{ route('atrium.showroom.owner-types.destroy', $type) }}">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="trash" :label="__('showroom::showroom.delete')" variant="danger" type="submit" data-testid="delete-owner-type" />
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

            @showroomCan('update', $type)
            <x-atrium::card data-testid="owner-type-details-card" :title="__('showroom::showroom.details')">
                <form method="POST" action="{{ route('atrium.showroom.owner-types.update', $type) }}" class="flex flex-col gap-4">
                    @csrf
                    @method('PATCH')
                    <x-atrium::form.input :name="'labels['.$locale.']'" :label="__('showroom::showroom.label_field', ['locale' => $locale])" :value="$type->labels[$locale] ?? null" wrapper="w-64" />

                    @include('showroom::ui.owner-types.rules', ['all' => array_values(array_diff($all, [])), 'type' => $type])

                    <div>
                        <x-atrium::icon-button icon="check" :label="__('showroom::showroom.save')" variant="primary" type="submit" data-testid="save-owner-type" />
                    </div>
                </form>
            </x-atrium::card>
            @endshowroomCan
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="aside" class="mt-5" />

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
