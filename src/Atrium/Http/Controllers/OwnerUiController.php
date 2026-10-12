<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Showroom\Atrium\Support\Labels;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\DeleteOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\ListOwnersAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\ShowOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\UpdateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\ListOwnersData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Exceptions\ShowroomException;

final class OwnerUiController
{
    use AuthorizesScreens;

    public function index(Request $request): View
    {
        $this->authorizeScreen('viewAny', OwnerModel::class);

        $filters = ListOwnersData::from($request->query());

        /** @var view-string $view */
        $view = 'showroom::ui.owners.index';

        return view($view, [
            'owners' => app(ListOwnersAction::class)->execute($filters)->withQueryString(),
            'filters' => $filters->toArray(),
            'types' => OwnerTypeModel::query()->orderBy('sort_order')->orderBy('code')->pluck('code', 'code')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', OwnerModel::class);

        $owner = app(CreateOwnerAction::class)->execute(CreateOwnerData::fromValidated(Labels::fromForm($request->validate(CreateOwnerData::rules()))));

        return redirect()
            ->route('atrium.showroom.owners.show', $owner)
            ->with('status', __('showroom::showroom.owner_created'));
    }

    public function show(OwnerModel $owner): View
    {
        $this->authorizeScreen('view', $owner);

        /** @var view-string $view */
        $view = 'showroom::ui.owners.show';

        return view($view, [
            'owner' => app(ShowOwnerAction::class)->execute($owner),
            'types' => OwnerTypeModel::query()->orderBy('code')->pluck('code', 'code')->all(),
        ]);
    }

    public function update(Request $request, OwnerModel $owner): RedirectResponse
    {
        $this->authorizeScreen('update', $owner);

        $data = UpdateOwnerData::fromValidated(Labels::fromForm($request->validate(UpdateOwnerData::rules()), $owner->labels));

        app(UpdateOwnerAction::class)->execute($owner, $data);

        return redirect()
            ->route('atrium.showroom.owners.show', $owner)
            ->with('status', __('showroom::showroom.owner_updated'));
    }

    public function destroy(OwnerModel $owner): RedirectResponse
    {
        $this->authorizeScreen('delete', $owner);

        $parent = $owner->parent;

        try {
            app(DeleteOwnerAction::class)->execute($owner);
        } catch (ShowroomException $e) {
            return back()->withErrors(['owner' => $e->getMessage()]);
        }

        return ($parent !== null
            ? redirect()->route('atrium.showroom.owners.show', $parent)
            : redirect()->route('atrium.showroom.owners.index'))
            ->with('status', __('showroom::showroom.owner_deleted'));
    }
}
