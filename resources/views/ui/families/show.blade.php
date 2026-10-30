@use(RefactorCircus\Showroom\Atrium\ScreenAccess)
@use(RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel)
@php($locale = app()->getLocale())
@php($canUpdate = ScreenAccess::allows('update', $family))

<x-atrium::layout :title="$family->label()">
    <x-atrium::page-header :title="$family->label()" :description="$family->code">
        <x-slot:actions>
            @showroomCan('delete', $family)
                <form method="POST" action="{{ route('atrium.showroom.families.destroy', $family) }}">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="trash" :label="__('showroom::showroom.delete')" variant="danger" type="submit" data-testid="delete-family" />
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

        <div class="mt-5">
            <x-atrium::flash class="mb-4" />

            {{-- One form: the Action replaces the whole membership, so every row is
                 posted back, with a remove box, plus one attribute to add. --}}
            <form method="POST" action="{{ route('atrium.showroom.families.update', $family) }}">
                @csrf
                @method('PATCH')

                <fieldset class="flex min-w-0 flex-col gap-5" @disabled(! $canUpdate)>

                    <x-atrium::card :title="__('showroom::showroom.details')">
                        <div class="flex flex-wrap items-start gap-3">
                            <x-atrium::form.input
                                :name="'labels['.$locale.']'"
                                :label="__('showroom::showroom.label_field', ['locale' => $locale])"
                                :value="$family->labels[$locale] ?? null"
                                wrapper="w-64" />

                            <x-atrium::form.select
                                name="label_attribute"
                                :label="__('showroom::showroom.label_attribute')"
                                :hint="__('showroom::showroom.label_attribute_hint')"
                                :placeholder="__('showroom::showroom.no_label_attribute')"
                                :options="$labelCandidates"
                                :selected="$family->labelAttribute?->code"
                                wrapper="w-64" />

                            <x-atrium::form.input name="sort_order" type="number" min="0" :label="__('showroom::showroom.sort_order')" :value="$family->sort_order" wrapper="w-28" />
                        </div>
                    </x-atrium::card>

                    <x-atrium::card :title="__('showroom::showroom.family_attributes')">
                        @if ($family->familyAttributes->isEmpty())
                            <x-atrium::empty-state :title="__('showroom::showroom.no_family_attributes')" />
                        @else
                            <x-atrium::table compact>
                                <x-slot:head>
                                    <x-atrium::table.row>
                                        <x-atrium::table.cell heading>{{ __('showroom::showroom.code') }}</x-atrium::table.cell>
                                        <x-atrium::table.cell heading>{{ __('showroom::showroom.type') }}</x-atrium::table.cell>
                                        <x-atrium::table.cell heading>{{ __('showroom::showroom.group') }}</x-atrium::table.cell>
                                        <x-atrium::table.cell heading>{{ __('showroom::showroom.required') }}</x-atrium::table.cell>
                                        <x-atrium::table.cell heading>{{ __('showroom::showroom.required_channels') }}</x-atrium::table.cell>
                                        <x-atrium::table.cell heading>{{ __('showroom::showroom.sort_order') }}</x-atrium::table.cell>
                                        <x-atrium::table.cell heading>{{ __('showroom::showroom.remove') }}</x-atrium::table.cell>
                                    </x-atrium::table.row>
                                </x-slot:head>

                                @foreach ($family->familyAttributes as $index => $attribute)
                                    <x-atrium::table.row>
                                        <x-atrium::table.cell>
                                            <input type="hidden" name="attributes[{{ $index }}][attribute]" value="{{ $attribute->code }}">
                                            <a class="font-mono underline-offset-2 hover:underline"
                                               href="{{ route('atrium.showroom.attributes.show', $attribute) }}">{{ $attribute->code }}</a>
                                        </x-atrium::table.cell>
                                        <x-atrium::table.cell><x-atrium::badge>{{ $attribute->type->value }}</x-atrium::badge></x-atrium::table.cell>
                                        <x-atrium::table.cell>{{ $attribute->group?->label() ?? __('showroom::showroom.none') }}</x-atrium::table.cell>
                                        <x-atrium::table.cell>
                                            <input type="hidden" name="attributes[{{ $index }}][is_required]" value="0">
                                            <x-atrium::form.checkbox bare :name="'attributes['.$index.'][is_required]'" :checked="(bool) $attribute->pivot->is_required" />
                                        </x-atrium::table.cell>
                                        <x-atrium::table.cell>
                                            @php($channels = $attribute->pivot->required_channels)
                                            <x-atrium::form.input bare :name="'attributes['.$index.'][required_channels]'"
                                                :value="implode(', ', is_string($channels) ? (json_decode($channels, true) ?? []) : (array) $channels)"
                                                :placeholder="__('showroom::showroom.required_channels_hint')"
                                                class="w-40" />
                                        </x-atrium::table.cell>
                                        <x-atrium::table.cell>
                                            <x-atrium::form.input bare type="number" min="0" :name="'attributes['.$index.'][sort_order]'" :value="$attribute->pivot->sort_order" class="w-20" />
                                        </x-atrium::table.cell>
                                        <x-atrium::table.cell>
                                            <x-atrium::form.checkbox bare :name="'attributes['.$index.'][remove]'" />
                                        </x-atrium::table.cell>
                                    </x-atrium::table.row>
                                @endforeach
                            </x-atrium::table>
                        @endif

                        @if ($canUpdate)
                        <div class="mt-4 flex flex-wrap items-start gap-3">
                            <x-atrium::form.select
                                name="add_attribute"
                                :label="__('showroom::showroom.add_attribute')"
                                :placeholder="__('showroom::showroom.choose_attribute')"
                                :options="$available->mapWithKeys(fn ($attribute) => [$attribute->code => $attribute->code.' ('.$attribute->type->value.')'])"
                                wrapper="w-72" />

                            <input type="hidden" name="add_required" value="0">
                            <x-atrium::form.actions>
                                <x-atrium::form.checkbox name="add_required" :label="__('showroom::showroom.required')" wrapper="h-9 w-auto justify-center" />
                            </x-atrium::form.actions>
                        </div>
                        @endif
                    </x-atrium::card>

                    @if ($canUpdate)
                        <div>
                            <x-atrium::icon-button icon="check" :label="__('showroom::showroom.save')" variant="primary" type="submit" data-testid="save-family" />
                        </div>
                    @endif
                </fieldset>
            </form>

            <x-atrium::card :title="__('showroom::showroom.family_variants')" class="mt-5">
                @if ($family->variants->isNotEmpty())
                    <ul class="mb-4 flex flex-wrap gap-2">
                        @foreach ($family->variants as $variant)
                            <li><a class="font-mono text-sm underline-offset-2 hover:underline" href="{{ route('atrium.showroom.family-variants.show', $variant) }}">{{ $variant->code }}</a></li>
                        @endforeach
                    </ul>
                @endif

                {{-- Two fixed levels; leave the second empty for a one-level variant. --}}
                @showroomCan('create', FamilyVariantModel::class)
                <form method="POST" action="{{ route('atrium.showroom.family-variants.store') }}" class="flex flex-col gap-4">
                    @csrf
                    <input type="hidden" name="family" value="{{ $family->code }}">
                    <x-atrium::form.input name="code" :label="__('showroom::showroom.code')" required wrapper="w-64" />

                    @foreach ([0, 1] as $level)
                        <fieldset class="rounded-radius border border-outline p-3 dark:border-outline-dark">
                            <legend class="px-1 text-sm font-medium">{{ __('showroom::showroom.level', ['level' => $level + 1]) }}</legend>

                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach (['axes' => __('showroom::showroom.axes'), 'attributes' => __('showroom::showroom.attributes')] as $kind => $heading)
                                    <div>
                                        <p class="mb-1 text-xs font-medium uppercase opacity-70">{{ $heading }}</p>
                                        <div class="flex flex-wrap gap-3">
                                            @foreach ($family->familyAttributes as $attribute)
                                                <label class="flex items-center gap-1.5 text-sm">
                                                    <x-atrium::form.checkbox bare :id="'atrium-levels-'.$level.'-'.$kind.'-'.$attribute->code" :name="'levels['.$level.']['.$kind.'][]'" :value="$attribute->code" />
                                                    <span class="font-mono text-xs">{{ $attribute->code }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach

                    <div>
                        <x-atrium::icon-button icon="plus" :label="__('showroom::showroom.create_family_variant')" variant="primary" type="submit" data-testid="create-family-variant" />
                    </div>
                </form>
                @endshowroomCan
            </x-atrium::card>
        </div>

        <x-atrium::screen-sections :screen="$resolved" slot="aside" class="mt-5" />

        <x-atrium::screen-sections :screen="$resolved" slot="after" class="mt-5" />
    @endif
</x-atrium::layout>
