<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Showroom\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Showroom\Atrium\Support\Labels;
use RefactorCircus\Showroom\Atrium\Support\Screens;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Channel\Actions\CreateChannelAction;
use RefactorCircus\Showroom\Domains\Channel\Actions\CreateLocaleAction;
use RefactorCircus\Showroom\Domains\Channel\Actions\DeleteChannelAction;
use RefactorCircus\Showroom\Domains\Channel\Actions\DeleteLocaleAction;
use RefactorCircus\Showroom\Domains\Channel\Actions\ListChannelsAction;
use RefactorCircus\Showroom\Domains\Channel\Actions\ListLocalesAction;
use RefactorCircus\Showroom\Domains\Channel\Actions\ShowChannelAction;
use RefactorCircus\Showroom\Domains\Channel\Actions\UpdateChannelAction;
use RefactorCircus\Showroom\Domains\Channel\Data\CreateChannelData;
use RefactorCircus\Showroom\Domains\Channel\Data\CreateLocaleData;
use RefactorCircus\Showroom\Domains\Channel\Data\ListChannelsData;
use RefactorCircus\Showroom\Domains\Channel\Data\ListLocalesData;
use RefactorCircus\Showroom\Domains\Channel\Data\UpdateChannelData;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;
use RefactorCircus\Showroom\Exceptions\ShowroomException;

/**
 * Channels and the locales they publish in, managed on one page.
 */
final class ChannelUiController
{
    use AuthorizesScreens;

    public function index(Request $request): View
    {
        $this->authorizeScreen('viewAny', ChannelModel::class);

        /** @var view-string $view */
        $view = 'showroom::ui.channels.index';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::list('showroom.channels', ChannelModel::class), $request),
            'channels' => app(ListChannelsAction::class)->execute(new ListChannelsData(perPage: 100)),
            'locales' => app(ListLocalesAction::class)->execute(new ListLocalesData(perPage: 100)),
            'allLocales' => LocaleModel::query()->orderBy('code')->pluck('code')->all(),
            'trees' => $this->trees(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', ChannelModel::class);

        $channel = app(CreateChannelAction::class)->execute(
            CreateChannelData::fromValidated(Labels::fromForm(Validator::validate($this->fromForm($request), CreateChannelData::rules()))),
        );

        return redirect()
            ->route('atrium.showroom.channels.show', $channel)
            ->with('status', __('showroom::showroom.channel_created'));
    }

    public function show(Request $request, ChannelModel $channel): View
    {
        $this->authorizeScreen('view', $channel);

        $channel = app(ShowChannelAction::class)->execute($channel);

        /** @var view-string $view */
        $view = 'showroom::ui.channels.show';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::detail('showroom.channel', $channel, ChannelModel::class), $request),
            'channel' => $channel,
            'allLocales' => LocaleModel::query()->orderBy('code')->pluck('code')->all(),
            'trees' => $this->trees(),
        ]);
    }

    public function update(Request $request, ChannelModel $channel): RedirectResponse
    {
        $this->authorizeScreen('update', $channel);

        app(UpdateChannelAction::class)->execute(
            $channel,
            UpdateChannelData::fromValidated(Labels::fromForm(Validator::validate($this->fromForm($request), UpdateChannelData::rules()), $channel->labels)),
        );

        return redirect()
            ->route('atrium.showroom.channels.show', $channel)
            ->with('status', __('showroom::showroom.channel_updated'));
    }

    public function destroy(ChannelModel $channel): RedirectResponse
    {
        $this->authorizeScreen('delete', $channel);

        app(DeleteChannelAction::class)->execute($channel);

        return redirect()
            ->route('atrium.showroom.channels.index')
            ->with('status', __('showroom::showroom.channel_deleted'));
    }

    public function storeLocale(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', LocaleModel::class);

        app(CreateLocaleAction::class)->execute(CreateLocaleData::fromValidated(Labels::fromForm($request->validate(CreateLocaleData::rules()))));

        return redirect()
            ->route('atrium.showroom.channels.index')
            ->with('status', __('showroom::showroom.locale_created'));
    }

    public function destroyLocale(LocaleModel $locale): RedirectResponse
    {
        $this->authorizeScreen('delete', $locale);

        try {
            app(DeleteLocaleAction::class)->execute($locale);
        } catch (ShowroomException $e) {
            return back()->withErrors(['locale' => $e->getMessage()]);
        }

        return redirect()
            ->route('atrium.showroom.channels.index')
            ->with('status', __('showroom::showroom.locale_deleted'));
    }

    /**
     * Currencies arrive as one comma-separated field; an empty tree select
     * means none.
     *
     * @return array<string, mixed>
     */
    private function fromForm(Request $request): array
    {
        $input = $request->except(['_token', '_method']);

        if (is_string($input['currencies'] ?? null)) {
            $input['currencies'] = array_values(array_filter(array_map(
                fn (string $code): string => strtoupper(trim($code)),
                explode(',', $input['currencies']),
            )));
        }

        if (array_key_exists('locales', $input)) {
            $input['locales'] = array_values(array_filter((array) $input['locales']));
        }

        if (($input['category_tree'] ?? null) === '') {
            $input['category_tree'] = null;
        }

        return $input;
    }

    /**
     * @return array<string, string>
     */
    private function trees(): array
    {
        return CategoryModel::query()
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (CategoryModel $tree): array => [$tree->code => $tree->label()])
            ->all();
    }
}
