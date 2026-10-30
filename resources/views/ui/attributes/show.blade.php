@use(RefactorCircus\Showroom\Atrium\ScreenAccess)
@use(RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel)
@php($locale = app()->getLocale())
@php($canUpdate = ScreenAccess::allows('update', $attribute))

<x-atrium::layout :title="$attribute->label()">
    <x-atrium::page-header :title="$attribute->label()" :description="$attribute->code">
        <x-slot:actions>
            <x-atrium::badge>{{ $attribute->type->value }}</x-atrium::badge>

            @showroomCan('delete', $attribute)
                <form method="POST" action="{{ route('atrium.showroom.attributes.destroy', $attribute) }}">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="trash" :label="__('showroom::showroom.delete')" variant="danger" type="submit" data-testid="delete-attribute" />
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

            <x-atrium::card :title="__('showroom::showroom.details')">
                <form method="POST" action="{{ route('atrium.showroom.attributes.update', $attribute) }}">
                    @csrf
                    @method('PATCH')

                    <fieldset class="flex max-w-xl min-w-0 flex-col gap-4" @disabled(! $canUpdate)>

                        <x-atrium::form.select
                            name="group"
                            :label="__('showroom::showroom.group')"
                            :placeholder="__('showroom::showroom.no_group')"
                            :options="$groups"
                            :selected="old('group', $attribute->group?->code)" />

                        <x-atrium::form.input
                            :name="'labels['.$locale.']'"
                            :label="__('showroom::showroom.label_field', ['locale' => $locale])"
                            :value="$attribute->labels[$locale] ?? null" />

                        @include('showroom::ui.partials.flags', ['attribute' => $attribute])

                        <x-atrium::form.textarea
                            name="settings"
                            :label="__('showroom::showroom.settings')"
                            :hint="__('showroom::showroom.settings_hint')"
                            :value="$attribute->settings ? json_encode($attribute->settings, JSON_PRETTY_PRINT) : null"
                            rows="4"
                            class="font-mono" />

                        <x-atrium::form.input name="sort_order" type="number" min="0" :label="__('showroom::showroom.sort_order')" :value="$attribute->sort_order" wrapper="w-32" />

                        @if ($canUpdate)
                            <div>
                                <x-atrium::icon-button icon="check" :label="__('showroom::showroom.save')" variant="primary" type="submit" data-testid="save-attribute" />
                            </div>
                        @endif
                    </fieldset>
                </form>
            </x-atrium::card>

            @if ($attribute->type->hasOptions())
                <x-atrium::card :title="__('showroom::showroom.options')">
                    @if ($attribute->options->isEmpty())
                        <x-atrium::empty-state :title="__('showroom::showroom.no_options')" />
                    @else
                        <x-atrium::table compact>
                            <x-slot:head>
                                <x-atrium::table.row>
                                    <x-atrium::table.cell heading>{{ __('showroom::showroom.code') }}</x-atrium::table.cell>
                                    <x-atrium::table.cell heading>{{ __('showroom::showroom.label_column') }}</x-atrium::table.cell>
                                    <x-atrium::table.cell heading>{{ __('showroom::showroom.sort_order') }}</x-atrium::table.cell>
                                    <x-atrium::table.cell heading></x-atrium::table.cell>
                                </x-atrium::table.row>
                            </x-slot:head>

                            @foreach ($attribute->options as $option)
                                <x-atrium::table.row>
                                    <x-atrium::table.cell class="font-mono text-xs">{{ $option->code }}</x-atrium::table.cell>
                                    <x-atrium::table.cell>{{ $option->label() }}</x-atrium::table.cell>
                                    <x-atrium::table.cell class="tabular-nums">{{ $option->sort_order }}</x-atrium::table.cell>
                                    <x-atrium::table.cell>
                                        @showroomCan('delete', $option)
                                            <form method="POST" action="{{ route('atrium.showroom.attributes.options.destroy', [$attribute, $option]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <x-atrium::icon-button icon="trash" :label="__('showroom::showroom.delete')" variant="ghost" size="sm" type="submit" data-testid="delete-option" />
                                            </form>
                                        @endshowroomCan
                                    </x-atrium::table.cell>
                                </x-atrium::table.row>
                            @endforeach
                        </x-atrium::table>
                    @endif

                    {{-- As the API asks: update the attribute and create an option. --}}
                    @if ($canUpdate && ScreenAccess::allows('create', AttributeOptionModel::class))
                    <form method="POST" action="{{ route('atrium.showroom.attributes.options.store', $attribute) }}" class="mt-4 flex flex-wrap items-start gap-3">
                        @csrf
                        <x-atrium::form.input name="code" :label="__('showroom::showroom.code')" required wrapper="w-48" />
                        <x-atrium::form.input :name="'labels['.$locale.']'" :label="__('showroom::showroom.label_field', ['locale' => $locale])" wrapper="w-56" />
                        <x-atrium::form.input name="sort_order" type="number" min="0" :label="__('showroom::showroom.sort_order')" value="0" wrapper="w-28" />
                        <x-atrium::form.actions>
                            <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.add_option')" variant="primary" type="submit" data-testid="add-option" />
                        </x-atrium::form.actions>
                    </form>
                    @endif
                </x-atrium::card>
            @endif
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="aside" class="mt-5" />

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
