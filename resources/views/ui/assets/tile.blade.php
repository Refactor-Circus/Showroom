{{-- One asset as a card: a thumbnail for images, the file type otherwise.
     On the asset list (`rowScreen` given), the `row.actions` sections for
     the asset sit at the bottom of its card, outside the link. --}}
@isset($rowScreen)
    <div class="group flex flex-col gap-2 rounded-radius border border-outline p-2 hover:border-primary dark:border-outline-dark" data-asset="{{ $asset->code }}">
        <a href="{{ route('atrium.showroom.assets.show', $asset) }}" class="flex flex-col gap-2">
            @include('showroom::ui.assets.thumbnail')
        </a>
        <x-atrium::screen-sections :screen="$rowScreen" slot="row.actions" :row="$asset" />
    </div>
@else
    <a href="{{ route('atrium.showroom.assets.show', $asset) }}" class="group flex flex-col gap-2 rounded-radius border border-outline p-2 hover:border-primary dark:border-outline-dark" data-asset="{{ $asset->code }}">
        @include('showroom::ui.assets.thumbnail')
    </a>
@endisset
