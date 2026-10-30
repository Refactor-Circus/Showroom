<x-atrium::layout :title="__('showroom::showroom.transfers')">
    <x-atrium::page-header :title="__('showroom::showroom.transfers')" :description="__('showroom::showroom.transfers_description')">
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

            @unless ($active)
                <x-atrium::alert variant="warning">{{ __('showroom::showroom.impex_missing') }}</x-atrium::alert>
            @else
                <div class="grid gap-4 lg:grid-cols-2">
                    @showroomCan('create', \RefactorCircus\Showroom\Domains\Product\Models\ProductModel::class)
                    <x-atrium::card data-testid="import-card" :title="__('showroom::showroom.import')">
                        <form method="POST" action="{{ route('atrium.showroom.transfers.import') }}" enctype="multipart/form-data" class="flex flex-col gap-3">
                            @csrf
                            <x-atrium::form.file name="file" :label="__('showroom::showroom.import_file')" :hint="__('showroom::showroom.import_file_hint')" required />
                            <x-atrium::form.select name="mode" :label="__('showroom::showroom.mode')" :options="['upsert' => __('showroom::showroom.mode_upsert'), 'create' => __('showroom::showroom.mode_create'), 'update' => __('showroom::showroom.mode_update')]" selected="upsert" wrapper="w-56" />
                            <div>
                                <x-atrium::icon-button icon="arrow-up-tray" :label="__('showroom::showroom.start_import')" variant="primary" type="submit" data-testid="start-import" />
                            </div>
                        </form>
                    </x-atrium::card>
                    @endshowroomCan

                    @showroomCan('viewAny', \RefactorCircus\Showroom\Domains\Product\Models\ProductModel::class)
                    <x-atrium::card data-testid="export-card" :title="__('showroom::showroom.export')">
                        <form method="POST" action="{{ route('atrium.showroom.transfers.export') }}" class="flex flex-col gap-3">
                            @csrf
                            <div class="flex flex-wrap gap-3">
                                <x-atrium::form.select name="format" :label="__('showroom::showroom.format')" :options="['jsonl' => 'JSONL', 'csv' => 'CSV']" selected="jsonl" wrapper="w-32" />
                                <x-atrium::form.select name="family" :label="__('showroom::showroom.family')" :placeholder="__('showroom::showroom.all_families')" :options="$families" wrapper="w-44" />
                                <x-atrium::form.select name="category" :label="__('showroom::showroom.category')" :placeholder="__('showroom::showroom.none')" :options="$trees" wrapper="w-44" />
                                <x-atrium::form.select name="scope" :label="__('showroom::showroom.channel')" :placeholder="__('showroom::showroom.none')" :options="$channels" wrapper="w-44" />
                            </div>
                            <input type="hidden" name="published" value="0">
                            <x-atrium::form.checkbox name="published" :label="__('showroom::showroom.export_published')" />
                            <div>
                                <x-atrium::icon-button icon="arrow-down-tray" :label="__('showroom::showroom.start_export')" variant="primary" type="submit" data-testid="start-export" />
                            </div>
                        </form>
                    </x-atrium::card>
                    @endshowroomCan
                </div>

                <x-atrium::card :title="__('showroom::showroom.recent_runs')">
                    @if ($runs->isEmpty())
                        <x-atrium::empty-state :title="__('showroom::showroom.no_runs')" />
                    @else
                        <x-atrium::table compact>
                            <x-slot:head>
                                <x-atrium::table.row>
                                    <x-atrium::table.cell heading>{{ __('showroom::showroom.flow') }}</x-atrium::table.cell>
                                    <x-atrium::table.cell heading>{{ __('showroom::showroom.status') }}</x-atrium::table.cell>
                                    <x-atrium::table.cell heading>{{ __('showroom::showroom.result') }}</x-atrium::table.cell>
                                    <x-atrium::table.cell heading>{{ __('showroom::showroom.updated') }}</x-atrium::table.cell>
                                    <x-atrium::table.cell heading></x-atrium::table.cell>
                                </x-atrium::table.row>
                            </x-slot:head>

                            @foreach ($runs as ['run' => $run, 'result' => $result])
                                <x-atrium::table.row data-run="{{ $run->flow }}">
                                    <x-atrium::table.cell class="font-mono text-xs">{{ $run->flow }}</x-atrium::table.cell>
                                    <x-atrium::table.cell><x-atrium::status-dot :variant="\RefactorCircus\Showroom\Atrium\Badges::forRun($run->status->value)" :label="$run->status->value" data-status="{{ $run->status->value }}" /></x-atrium::table.cell>
                                    <x-atrium::table.cell class="text-xs">
                                        @if (is_array($result) && isset($result['asset']))
                                            <a class="font-mono underline-offset-2 hover:underline" href="{{ route('atrium.showroom.assets.show', $result['asset']) }}">{{ $result['asset'] }}</a>
                                            · {{ __('showroom::showroom.records', ['count' => $result['count'] ?? 0]) }}
                                        @elseif (is_array($result) && isset($result['total']))
                                            {{ __('showroom::showroom.import_counts', ['succeeded' => $result['succeeded'] ?? 0, 'failed' => $result['failed'] ?? 0, 'total' => $result['total']]) }}
                                        @elseif ($run->error)
                                            <span class="text-danger">{{ $run->error['message'] ?? '' }}</span>
                                        @endif
                                    </x-atrium::table.cell>
                                    <x-atrium::table.cell>{{ $run->updated_at?->diffForHumans() }}</x-atrium::table.cell>
                                    <x-atrium::table.cell><x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$run" /></x-atrium::table.cell>
                                </x-atrium::table.row>
                            @endforeach
                        </x-atrium::table>
                    @endif
                </x-atrium::card>
            @endunless
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
