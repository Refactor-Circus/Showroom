<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Showroom\Atrium\Support\Labels;
use RefactorCircus\Showroom\Domains\Category\Actions\CreateCategoryAction;
use RefactorCircus\Showroom\Domains\Category\Actions\DeleteCategoryAction;
use RefactorCircus\Showroom\Domains\Category\Actions\ListCategoriesAction;
use RefactorCircus\Showroom\Domains\Category\Actions\ShowCategoryAction;
use RefactorCircus\Showroom\Domains\Category\Actions\UpdateCategoryAction;
use RefactorCircus\Showroom\Domains\Category\Data\CreateCategoryData;
use RefactorCircus\Showroom\Domains\Category\Data\ListCategoriesData;
use RefactorCircus\Showroom\Domains\Category\Data\UpdateCategoryData;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Exceptions\ShowroomException;

final class CategoryUiController
{
    use AuthorizesScreens;

    public function index(Request $request): View
    {
        $this->authorizeScreen('viewAny', CategoryModel::class);

        /** @var view-string $view */
        $view = 'showroom::ui.categories.index';

        return view($view, [
            'trees' => app(ListCategoriesAction::class)->execute(ListCategoriesData::from(['roots' => true] + $request->query()))->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', CategoryModel::class);

        $category = app(CreateCategoryAction::class)->execute(CreateCategoryData::fromValidated(Labels::fromForm($request->validate(CreateCategoryData::rules()))));

        return redirect()
            ->route('atrium.showroom.categories.show', $category->parent ?? $category)
            ->with('status', __('showroom::showroom.category_created'));
    }

    public function show(CategoryModel $category): View
    {
        $this->authorizeScreen('view', $category);

        $category = app(ShowCategoryAction::class)->execute($category);

        /** @var view-string $view */
        $view = 'showroom::ui.categories.show';

        return view($view, [
            'category' => $category,
            // The whole branch beneath, grouped by parent for a nested list.
            'branch' => CategoryModel::query()
                ->subtreeOf($category)
                ->whereKeyNot($category->getKey())
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get()
                ->groupBy('parent_id'),
        ]);
    }

    public function update(Request $request, CategoryModel $category): RedirectResponse
    {
        $this->authorizeScreen('update', $category);

        $data = UpdateCategoryData::fromValidated(Labels::fromForm($request->validate(UpdateCategoryData::rules()), $category->labels));

        app(UpdateCategoryAction::class)->execute($category, $data);

        return redirect()
            ->route('atrium.showroom.categories.show', $category)
            ->with('status', __('showroom::showroom.category_updated'));
    }

    public function destroy(CategoryModel $category): RedirectResponse
    {
        $this->authorizeScreen('delete', $category);

        $parent = $category->parent;

        try {
            app(DeleteCategoryAction::class)->execute($category);
        } catch (ShowroomException $e) {
            return back()->withErrors(['category' => $e->getMessage()]);
        }

        return ($parent !== null
            ? redirect()->route('atrium.showroom.categories.show', $parent)
            : redirect()->route('atrium.showroom.categories.index'))
            ->with('status', __('showroom::showroom.category_deleted'));
    }
}
