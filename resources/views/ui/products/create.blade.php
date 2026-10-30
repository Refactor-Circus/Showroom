<x-atrium::layout :title="__('showroom::showroom.new_product')">
    <x-atrium::page-header :title="__('showroom::showroom.new_product')">
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

            <x-atrium::card>
                <form method="POST" action="{{ route('atrium.showroom.products.store') }}" class="flex max-w-xl flex-col gap-4">
                    @csrf

                    <x-atrium::form.input name="identifier" :label="__('showroom::showroom.identifier')" :hint="__('showroom::showroom.identifier_hint')" required />

                    <x-atrium::form.select
                        name="family"
                        :label="__('showroom::showroom.family')"
                        :placeholder="__('showroom::showroom.no_family')"
                        :options="$families"
                        :selected="old('family')" />

                    <div>
                        <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.create_product')" variant="primary" type="submit" data-testid="create-product" />
                    </div>
                </form>
            </x-atrium::card>
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="aside" class="mt-5" />

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
