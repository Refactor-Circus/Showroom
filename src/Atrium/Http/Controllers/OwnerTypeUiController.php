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
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerTypeAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\DeleteOwnerTypeAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\ListOwnerTypesAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\ShowOwnerTypeAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\UpdateOwnerTypeAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerTypeData;
use RefactorCircus\Showroom\Domains\Owner\Data\ListOwnerTypesData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerTypeData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Exceptions\ShowroomException;

final class OwnerTypeUiController
{
    use AuthorizesScreens;

    public function index(Request $request): View
    {
        $this->authorizeScreen('viewAny', OwnerTypeModel::class);

        /** @var view-string $view */
        $view = 'showroom::ui.owner-types.index';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::list('showroom.owner-types', OwnerTypeModel::class), $request),
            'types' => app(ListOwnerTypesAction::class)->execute(ListOwnerTypesData::from($request->query()))->withQueryString(),
            'all' => OwnerTypeModel::query()->orderBy('code')->pluck('code')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', OwnerTypeModel::class);

        $type = app(CreateOwnerTypeAction::class)->execute(
            CreateOwnerTypeData::fromValidated(Labels::fromForm(Validator::validate($this->fromForm($request), CreateOwnerTypeData::rules()))),
        );

        return redirect()
            ->route('atrium.showroom.owner-types.show', $type)
            ->with('status', __('showroom::showroom.owner_type_created'));
    }

    public function show(Request $request, OwnerTypeModel $ownerType): View
    {
        $this->authorizeScreen('view', $ownerType);

        $type = app(ShowOwnerTypeAction::class)->execute($ownerType);

        /** @var view-string $view */
        $view = 'showroom::ui.owner-types.show';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::detail('showroom.owner-type', $type, OwnerTypeModel::class), $request),
            'type' => $type,
            'all' => OwnerTypeModel::query()->orderBy('code')->pluck('code')->all(),
        ]);
    }

    public function update(Request $request, OwnerTypeModel $ownerType): RedirectResponse
    {
        $this->authorizeScreen('update', $ownerType);

        app(UpdateOwnerTypeAction::class)->execute(
            $ownerType,
            UpdateOwnerTypeData::fromValidated(Labels::fromForm(Validator::validate($this->fromForm($request), UpdateOwnerTypeData::rules()), $ownerType->labels)),
        );

        return redirect()
            ->route('atrium.showroom.owner-types.show', $ownerType)
            ->with('status', __('showroom::showroom.owner_type_updated'));
    }

    public function destroy(OwnerTypeModel $ownerType): RedirectResponse
    {
        $this->authorizeScreen('delete', $ownerType);

        try {
            app(DeleteOwnerTypeAction::class)->execute($ownerType);
        } catch (ShowroomException $e) {
            return back()->withErrors(['owner_type' => $e->getMessage()]);
        }

        return redirect()
            ->route('atrium.showroom.owner-types.index')
            ->with('status', __('showroom::showroom.owner_type_deleted'));
    }

    /**
     * The form's "any parent" switch stands for `parent_types: null`.
     *
     * @return array<string, mixed>
     */
    private function fromForm(Request $request): array
    {
        $input = $request->except(['_token', '_method', 'any_parent']);

        $input['parent_types'] = $request->boolean('any_parent')
            ? null
            : array_values(array_filter((array) $request->input('parent_types', [])));

        return $input;
    }
}
