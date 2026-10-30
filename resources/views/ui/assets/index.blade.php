@use(RefactorCircus\Showroom\Domains\Asset\Models\AssetModel)
@php($locale = app()->getLocale())

<x-atrium::layout :title="__('showroom::showroom.assets')">
    <x-atrium::page-header :title="__('showroom::showroom.assets')">
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

            @showroomCan('create', AssetModel::class)
            <x-atrium::card :title="__('showroom::showroom.upload_asset')">
                <form method="POST" action="{{ route('atrium.showroom.assets.store') }}" enctype="multipart/form-data" class="flex flex-wrap items-start gap-3">
                    @csrf
                    <x-atrium::form.file name="file" :label="__('showroom::showroom.file')" required wrapper="w-72" />
                    <x-atrium::form.input name="code" :label="__('showroom::showroom.code')" :hint="__('showroom::showroom.asset_code_hint')" wrapper="w-48" />
                    <x-atrium::form.input :name="'labels['.$locale.']'" :label="__('showroom::showroom.label_field', ['locale' => $locale])" wrapper="w-56" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="arrow-up-tray" :label="__('showroom::showroom.upload')" variant="primary" type="submit" data-testid="upload-asset" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>
            @endshowroomCan

            <x-atrium::card>
                <form method="GET" action="{{ route('atrium.showroom.assets.index') }}" class="flex flex-wrap items-start gap-3">
                    <x-atrium::form.input name="search" :label="__('showroom::showroom.search')" :value="$filters['search'] ?? null" wrapper="w-56" />
                    <x-atrium::form.select
                        name="type"
                        :label="__('showroom::showroom.type')"
                        :placeholder="__('showroom::showroom.all_types')"
                        :options="['image/*' => __('showroom::showroom.images'), 'application/pdf' => 'PDF', 'video/*' => __('showroom::showroom.videos')]"
                        :selected="$filters['type'] ?? null"
                        wrapper="w-40" />
                    <x-atrium::form.input name="product" :label="__('showroom::showroom.product')" :value="$filters['product'] ?? null" wrapper="w-44" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="funnel" :label="__('showroom::showroom.filter')" variant="primary" type="submit" />
                        <x-atrium::icon-button icon="x-mark" :label="__('showroom::showroom.clear')" variant="ghost" :href="route('atrium.showroom.assets.index')" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>

            @if ($assets->isEmpty())
                <x-atrium::empty-state :title="__('showroom::showroom.no_assets')" />
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($assets as $asset)
                        @include('showroom::ui.assets.tile', ['asset' => $asset, 'caption' => $asset->code, 'rowScreen' => $resolved])
                    @endforeach
                </div>

                <x-atrium::pagination :paginator="$assets" />
            @endif
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
