<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Showroom\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Showroom\Atrium\Support\CategoryCodes;
use RefactorCircus\Showroom\Atrium\Support\EditingSlot;
use RefactorCircus\Showroom\Atrium\Support\Screens;
use RefactorCircus\Showroom\Atrium\Support\ValueForm;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;
use RefactorCircus\Showroom\Domains\ProductModel\Actions\CreateProductModelAction;
use RefactorCircus\Showroom\Domains\ProductModel\Actions\DeleteProductModelAction;
use RefactorCircus\Showroom\Domains\ProductModel\Actions\ListProductModelsAction;
use RefactorCircus\Showroom\Domains\ProductModel\Actions\ShowProductModelAction;
use RefactorCircus\Showroom\Domains\ProductModel\Actions\UpdateProductModelAction;
use RefactorCircus\Showroom\Domains\ProductModel\Data\CreateProductModelData;
use RefactorCircus\Showroom\Domains\ProductModel\Data\ListProductModelsData;
use RefactorCircus\Showroom\Domains\ProductModel\Data\UpdateProductModelData;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;

final class ProductModelUiController
{
    use AuthorizesScreens;

    public function index(Request $request): View
    {
        $this->authorizeScreen('viewAny', ProductModelModel::class);

        // The page shows back what was searched for.
        $filters = $request->validate(ListProductModelsData::rules());

        /** @var view-string $view */
        $view = 'showroom::ui.product-models.index';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::list('showroom.product-models', ProductModelModel::class), $request),
            'models' => app(ListProductModelsAction::class)->execute(ListProductModelsData::fromValidated(['roots' => true] + $filters))->withQueryString(),
            'filters' => $filters,
            'variants' => FamilyVariantModel::query()->orderBy('code')->pluck('code', 'code')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', ProductModelModel::class);

        $model = app(CreateProductModelAction::class)->execute(CreateProductModelData::from($request->all()));

        return redirect()
            ->route('atrium.showroom.product-models.show', $model)
            ->with('status', __('showroom::showroom.product_model_created'));
    }

    public function show(Request $request, ProductModelModel $productModel): View
    {
        $this->authorizeScreen('view', $productModel);

        $model = app(ShowProductModelAction::class)->execute($productModel);

        /** @var view-string $view */
        $view = 'showroom::ui.product-models.show';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::detail('showroom.product-model', $model, ProductModelModel::class), $request),
            'model' => $model,
            'attributes' => $model->settableAttributes()->load('options'),
            'slot' => EditingSlot::fromRequest($request),
            'inherited' => $model->inheritedValues(),
        ]);
    }

    public function update(Request $request, ProductModelModel $productModel): RedirectResponse
    {
        $this->authorizeScreen('update', $productModel);

        /** @var array<string, mixed> $input */
        $input = (array) $request->input('v', []);

        $slot = EditingSlot::fromRequest($request);
        $data = ['values' => ValueForm::toValues($input, $productModel->settableAttributes(), $slot)];

        if ($request->has('categories')) {
            $data['categories'] = CategoryCodes::fromForm($request->input('categories'));
        }

        if ($productModel->parent_id === null && $request->has('owner')) {
            $data['owner'] = $request->filled('owner') ? $request->string('owner')->toString() : null;
        }

        // The form builds the payload, so it is held to the Action's rules too.
        app(UpdateProductModelAction::class)->execute($productModel, UpdateProductModelData::from($data));

        return redirect()
            ->route('atrium.showroom.product-models.show', ['productModel' => $productModel, ...$slot->query()])
            ->with('status', __('showroom::showroom.product_model_updated'));
    }

    public function destroy(ProductModelModel $productModel): RedirectResponse
    {
        $this->authorizeScreen('delete', $productModel);

        $parent = $productModel->parent;

        app(DeleteProductModelAction::class)->execute($productModel);

        return $parent !== null
            ? redirect()->route('atrium.showroom.product-models.show', $parent)->with('status', __('showroom::showroom.product_model_deleted'))
            : redirect()->route('atrium.showroom.product-models.index')->with('status', __('showroom::showroom.product_model_deleted'));
    }
}
