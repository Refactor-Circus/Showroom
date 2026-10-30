<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Showroom\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Showroom\Atrium\Support\Labels;
use RefactorCircus\Showroom\Atrium\Support\Screens;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeGroupAction;
use RefactorCircus\Showroom\Domains\Attribute\Actions\DeleteAttributeGroupAction;
use RefactorCircus\Showroom\Domains\Attribute\Actions\ListAttributeGroupsAction;
use RefactorCircus\Showroom\Domains\Attribute\Actions\ShowAttributeGroupAction;
use RefactorCircus\Showroom\Domains\Attribute\Actions\UpdateAttributeGroupAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeGroupData;
use RefactorCircus\Showroom\Domains\Attribute\Data\ListAttributeGroupsData;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpdateAttributeGroupData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Exceptions\ShowroomException;

final class AttributeGroupUiController
{
    use AuthorizesScreens;

    public function index(Request $request): View
    {
        $this->authorizeScreen('viewAny', AttributeGroupModel::class);

        // Filters are validated by the same rules as the JSON API, so both
        // accept exactly the same query.
        $filters = $request->validate(ListAttributeGroupsData::rules());

        /** @var view-string $view */
        $view = 'showroom::ui.attribute-groups.index';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::list('showroom.attribute-groups', AttributeGroupModel::class), $request),
            'groups' => app(ListAttributeGroupsAction::class)->execute(ListAttributeGroupsData::fromValidated($filters))->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', AttributeGroupModel::class);

        $data = CreateAttributeGroupData::fromValidated(Labels::fromForm($request->validate(CreateAttributeGroupData::rules())));

        $group = app(CreateAttributeGroupAction::class)->execute($data);

        return redirect()
            ->route('atrium.showroom.attribute-groups.show', $group)
            ->with('status', __('showroom::showroom.group_created'));
    }

    public function show(Request $request, AttributeGroupModel $group): View
    {
        $this->authorizeScreen('view', $group);

        $group = app(ShowAttributeGroupAction::class)->execute($group);

        /** @var view-string $view */
        $view = 'showroom::ui.attribute-groups.show';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::detail('showroom.attribute-group', $group, AttributeGroupModel::class), $request),
            'group' => $group,
        ]);
    }

    public function update(Request $request, AttributeGroupModel $group): RedirectResponse
    {
        $this->authorizeScreen('update', $group);

        $data = UpdateAttributeGroupData::fromValidated(Labels::fromForm($request->validate(UpdateAttributeGroupData::rules()), $group->labels));

        app(UpdateAttributeGroupAction::class)->execute($group, $data);

        return redirect()
            ->route('atrium.showroom.attribute-groups.show', $group)
            ->with('status', __('showroom::showroom.group_updated'));
    }

    public function destroy(AttributeGroupModel $group): RedirectResponse
    {
        $this->authorizeScreen('delete', $group);

        try {
            app(DeleteAttributeGroupAction::class)->execute($group);
        } catch (ShowroomException $e) {
            return back()->withErrors(['group' => $e->getMessage()]);
        }

        return redirect()
            ->route('atrium.showroom.attribute-groups.index')
            ->with('status', __('showroom::showroom.group_deleted'));
    }
}
