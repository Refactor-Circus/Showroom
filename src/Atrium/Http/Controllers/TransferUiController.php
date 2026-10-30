<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Impex\Domains\Run\Models\RunModel;
use RefactorCircus\Impex\Facades\Impex;
use RefactorCircus\Showroom\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Showroom\Atrium\ScreenAccess;
use RefactorCircus\Showroom\Atrium\Support\Screens;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Transfer\Actions\StartExportAction;
use RefactorCircus\Showroom\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Showroom\Domains\Transfer\Data\StartExportData;
use RefactorCircus\Showroom\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Showroom\Impex\ImpexIntegration;

/**
 * Imports and exports: start one, and follow the latest runs.
 */
final class TransferUiController
{
    use AuthorizesScreens;

    public function index(Request $request): View
    {
        $this->refuseUnless(ScreenAccess::transfers($request));

        $active = app(ImpexIntegration::class)->active();

        /** @var view-string $view */
        $view = 'showroom::ui.transfers.index';

        return view($view, [
            // Runs are Impex's records, so their row extensions are asked
            // about products, as this page's own check is.
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::list('showroom.transfers', ProductModel::class), $request),
            'active' => $active,
            'runs' => $active
                ? RunModel::query()->where('flow', 'like', 'showroom:%')->latest('created_at')->limit(20)->get()
                    ->map(fn (RunModel $run): array => ['run' => $run, 'result' => $run->status->value === 'completed' ? Impex::result($run) : null])
                : collect(),
            'families' => FamilyModel::query()->orderBy('code')->pluck('code', 'code')->all(),
            'channels' => ChannelModel::query()->orderBy('code')->pluck('code', 'code')->all(),
            'trees' => CategoryModel::query()->whereNull('parent_id')->orderBy('code')->pluck('code', 'code')->all(),
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', ProductModel::class);

        app(StartImportAction::class)->execute(StartImportData::from($request->all()));

        return back()->with('status', __('showroom::showroom.import_started'));
    }

    public function export(Request $request): RedirectResponse
    {
        $this->authorizeScreen('viewAny', ProductModel::class);

        // Blank form fields filter nothing.
        $data = array_filter($request->validate(StartExportData::rules()), fn (mixed $value): bool => $value !== null && $value !== '');

        app(StartExportAction::class)->execute(StartExportData::fromValidated($data));

        return back()->with('status', __('showroom::showroom.export_started'));
    }
}
