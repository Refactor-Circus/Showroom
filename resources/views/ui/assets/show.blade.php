@php($locale = app()->getLocale())

<x-atrium::layout :title="$asset->label()">
    <x-atrium::page-header :title="$asset->label()" :description="$asset->filename">
        <x-slot:actions>
            <x-atrium::icon-button icon="arrow-top-right-on-square" :label="__('showroom::showroom.open_file')" variant="outline" :href="$asset->url()" target="_blank" />

            @showroomCan('delete', $asset)
                <form method="POST" action="{{ route('atrium.showroom.assets.destroy', $asset) }}">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="trash" :label="__('showroom::showroom.delete')" variant="danger" type="submit" data-testid="delete-asset" />
                </form>
            @endshowroomCan

            <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
        </x-slot:actions>
    </x-atrium::page-header>

    <x-atrium::screen-tabs :screen="$resolved" class="mt-4" />

    @if ($resolved->openRegisteredTab !== null)
        <x-atrium::screen-tab-panel :screen="$resolved" class="mt-5" />
    @else
        {{-- Sections span the grid as the page's own full-width rows do; `aside`
             sits in the narrow side column, under the file. --}}
        <div class="mt-5 grid gap-5 lg:grid-cols-3">
            <x-atrium::screen-sections :screen="$resolved" slot="before" class="lg:col-span-3" />

            <x-atrium::flash class="lg:col-span-3" />

            <div class="flex flex-col gap-5" data-testid="asset-side-column">
                <x-atrium::card>
                    @if ($asset->isImage())
                        <img src="{{ $asset->url() }}" alt="{{ $asset->label() }}" class="w-full rounded-radius object-contain">
                    @endif

                    <x-atrium::description-list @class(['mt-3' => $asset->isImage()])>
                        <x-atrium::description-list.item :term="__('showroom::showroom.code')" class="font-mono">{{ $asset->code }}</x-atrium::description-list.item>
                        <x-atrium::description-list.item :term="__('showroom::showroom.type')">{{ $asset->mime_type ?? __('showroom::showroom.none') }}</x-atrium::description-list.item>
                        <x-atrium::description-list.item :term="__('showroom::showroom.size')" class="tabular-nums">{{ number_format($asset->size / 1024, 1) }} KB</x-atrium::description-list.item>
                        <x-atrium::description-list.item :term="__('showroom::showroom.checksum')" class="break-all font-mono text-xs">{{ $asset->checksum }}</x-atrium::description-list.item>
                    </x-atrium::description-list>
                </x-atrium::card>

                <x-atrium::screen-sections :screen="$resolved" slot="aside" />
            </div>

            <div class="flex flex-col gap-5 lg:col-span-2">
                @showroomCan('update', $asset)
                <x-atrium::card :title="__('showroom::showroom.details')" data-testid="asset-details-card">
                    <form method="POST" action="{{ route('atrium.showroom.assets.update', $asset) }}" enctype="multipart/form-data" class="flex flex-wrap items-start gap-3">
                        @csrf
                        @method('PATCH')
                        <x-atrium::form.input :name="'labels['.$locale.']'" :label="__('showroom::showroom.label_field', ['locale' => $locale])" :value="$asset->labels[$locale] ?? null" wrapper="w-64" />
                        <x-atrium::form.file name="file" :label="__('showroom::showroom.replace_file')" wrapper="w-72" />
                        <x-atrium::form.actions>
                            <x-atrium::icon-button icon="check" :label="__('showroom::showroom.save')" variant="primary" type="submit" data-testid="save-asset" />
                        </x-atrium::form.actions>
                    </form>
                </x-atrium::card>
                @endshowroomCan

                <x-atrium::card :title="__('showroom::showroom.links')">
                    @php($canLink = \RefactorCircus\Showroom\Atrium\ScreenAccess::allows('update', $asset))
                    @php($links = [
                        'product' => $asset->products,
                        'product_model' => $asset->productModels,
                        'owner' => $asset->owners,
                    ])

                    @if (collect($links)->flatten()->isEmpty())
                        <x-atrium::empty-state :title="__('showroom::showroom.no_links')" />
                    @else
                        <x-atrium::table compact>
                            <x-slot:head>
                                <x-atrium::table.row>
                                    <x-atrium::table.cell heading>{{ __('showroom::showroom.type') }}</x-atrium::table.cell>
                                    <x-atrium::table.cell heading>{{ __('showroom::showroom.target') }}</x-atrium::table.cell>
                                    <x-atrium::table.cell heading>{{ __('showroom::showroom.role') }}</x-atrium::table.cell>
                                    <x-atrium::table.cell heading></x-atrium::table.cell>
                                </x-atrium::table.row>
                            </x-slot:head>

                            @foreach ($links as $type => $records)
                                @foreach ($records as $record)
                                    @php($target = $type === 'product' ? $record->identifier : $record->code)
                                    <x-atrium::table.row>
                                        <x-atrium::table.cell>{{ str_replace('_', ' ', $type) }}</x-atrium::table.cell>
                                        <x-atrium::table.cell class="font-mono text-xs">{{ $target }}</x-atrium::table.cell>
                                        <x-atrium::table.cell>{{ $record->pivot->role }}</x-atrium::table.cell>
                                        <x-atrium::table.cell>
                                            @if ($canLink)
                                            <form method="POST" action="{{ route('atrium.showroom.assets.detach', $asset) }}">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="type" value="{{ $type }}">
                                                <input type="hidden" name="target" value="{{ $target }}">
                                                <input type="hidden" name="role" value="{{ $record->pivot->role }}">
                                                <x-atrium::icon-button icon="link-slash" :label="__('showroom::showroom.unlink')" variant="ghost" size="sm" type="submit" data-testid="detach-asset" />
                                            </form>
                                            @endif
                                        </x-atrium::table.cell>
                                    </x-atrium::table.row>
                                @endforeach
                            @endforeach
                        </x-atrium::table>
                    @endif

                    @if ($canLink)
                    <form method="POST" action="{{ route('atrium.showroom.assets.attach', $asset) }}" class="mt-4 flex flex-wrap items-start gap-3">
                        @csrf
                        <x-atrium::form.select name="type" :label="__('showroom::showroom.type')" :options="$types" required wrapper="w-40" />
                        <x-atrium::form.input name="target" :label="__('showroom::showroom.target')" :hint="__('showroom::showroom.target_hint')" required wrapper="w-48" />
                        <x-atrium::form.input name="role" :label="__('showroom::showroom.role')" value="media" wrapper="w-36" />
                        <x-atrium::form.actions>
                            <x-atrium::icon-button icon="link" :label="__('showroom::showroom.link')" variant="primary" type="submit" data-testid="attach-asset" />
                        </x-atrium::form.actions>
                    </form>
                    @endif
                </x-atrium::card>
            </div>

            <x-atrium::screen-sections :screen="$resolved" slot="after" class="lg:col-span-3" />
        </div>
    @endif
</x-atrium::layout>
