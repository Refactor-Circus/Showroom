<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Showroom\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Showroom\Atrium\Support\Labels;
use RefactorCircus\Showroom\Atrium\Support\Screens;
use RefactorCircus\Showroom\Domains\Asset\Actions\AttachAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Actions\CreateAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Actions\DeleteAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Actions\DetachAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Actions\ListAssetsAction;
use RefactorCircus\Showroom\Domains\Asset\Actions\ShowAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Actions\UpdateAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Data\AttachAssetData;
use RefactorCircus\Showroom\Domains\Asset\Data\CreateAssetData;
use RefactorCircus\Showroom\Domains\Asset\Data\DetachAssetData;
use RefactorCircus\Showroom\Domains\Asset\Data\ListAssetsData;
use RefactorCircus\Showroom\Domains\Asset\Data\UpdateAssetData;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Asset\Services\AssetLinks;

final class AssetUiController
{
    use AuthorizesScreens;

    public function index(Request $request): View
    {
        $this->authorizeScreen('viewAny', AssetModel::class);

        $filters = ListAssetsData::from($request->query());

        /** @var view-string $view */
        $view = 'showroom::ui.assets.index';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::list('showroom.assets', AssetModel::class), $request),
            'assets' => app(ListAssetsAction::class)->execute($filters)->withQueryString(),
            'filters' => $filters->toArray(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', AssetModel::class);

        $asset = app(CreateAssetAction::class)->execute(CreateAssetData::fromValidated(Labels::fromForm($request->validate(CreateAssetData::rules()))));

        return redirect()
            ->route('atrium.showroom.assets.show', $asset)
            ->with('status', __('showroom::showroom.asset_created'));
    }

    /**
     * Upload a file and link it to the record whose page it came from, in
     * one step.
     */
    public function upload(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', AssetModel::class);

        $link = AttachAssetData::from($request->all());
        $source = CreateAssetData::from($request->all());

        DB::transaction(function () use ($source, $link): void {
            $asset = app(CreateAssetAction::class)->execute($source);

            // Linking is an update of the new asset, as the API's attach
            // call asks; refusing it rolls the upload back.
            $this->authorizeScreen('update', $asset);

            app(AttachAssetAction::class)->execute($asset, $link);
        });

        return back()->with('status', __('showroom::showroom.asset_attached'));
    }

    public function show(Request $request, AssetModel $asset): View
    {
        $this->authorizeScreen('view', $asset);

        $asset = app(ShowAssetAction::class)->execute($asset);

        /** @var view-string $view */
        $view = 'showroom::ui.assets.show';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::detail('showroom.asset', $asset, AssetModel::class), $request),
            'asset' => $asset,
            'types' => array_combine(array_keys(AssetLinks::TYPES), array_keys(AssetLinks::TYPES)),
        ]);
    }

    public function update(Request $request, AssetModel $asset): RedirectResponse
    {
        $this->authorizeScreen('update', $asset);

        app(UpdateAssetAction::class)->execute($asset, UpdateAssetData::fromValidated(Labels::fromForm($request->validate(UpdateAssetData::rules()), $asset->labels)));

        return redirect()
            ->route('atrium.showroom.assets.show', $asset)
            ->with('status', __('showroom::showroom.asset_updated'));
    }

    public function destroy(AssetModel $asset): RedirectResponse
    {
        $this->authorizeScreen('delete', $asset);

        app(DeleteAssetAction::class)->execute($asset);

        return redirect()
            ->route('atrium.showroom.assets.index')
            ->with('status', __('showroom::showroom.asset_deleted'));
    }

    public function attach(Request $request, AssetModel $asset): RedirectResponse
    {
        $this->authorizeScreen('update', $asset);

        app(AttachAssetAction::class)->execute($asset, AttachAssetData::from($request->all()));

        return back()->with('status', __('showroom::showroom.asset_attached'));
    }

    public function detach(Request $request, AssetModel $asset): RedirectResponse
    {
        $this->authorizeScreen('update', $asset);

        app(DetachAssetAction::class)->execute($asset, DetachAssetData::from($request->all()));

        return back()->with('status', __('showroom::showroom.asset_detached'));
    }
}
