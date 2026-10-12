<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RefactorCircus\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Showroom\Atrium\Support\CategoryCodes;
use RefactorCircus\Showroom\Atrium\Support\EditingSlot;
use RefactorCircus\Showroom\Atrium\Support\ValueForm;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Product\Actions\CreateProductAction;
use RefactorCircus\Showroom\Domains\Product\Actions\DeleteProductAction;
use RefactorCircus\Showroom\Domains\Product\Actions\ListProductsAction;
use RefactorCircus\Showroom\Domains\Product\Actions\ShowProductAction;
use RefactorCircus\Showroom\Domains\Product\Actions\UpdateProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;
use RefactorCircus\Showroom\Domains\Product\Data\ListProductsData;
use RefactorCircus\Showroom\Domains\Product\Data\UpdateProductData;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Workflow\Actions\ListProductVersionsAction;
use RefactorCircus\Showroom\Domains\Workflow\Actions\RevertProductAction;
use RefactorCircus\Showroom\Domains\Workflow\Actions\TransitionProductAction;
use RefactorCircus\Showroom\Domains\Workflow\Data\ListProductVersionsData;
use RefactorCircus\Showroom\Domains\Workflow\Data\RevertProductData;
use RefactorCircus\Showroom\Domains\Workflow\Data\TransitionProductData;
use RefactorCircus\Showroom\Domains\Workflow\Enums\Transition;
use RefactorCircus\Showroom\Exceptions\ShowroomException;

final class ProductUiController
{
    use AuthorizesScreens;

    public function index(Request $request): View
    {
        $this->authorizeScreen('viewAny', ProductModel::class);

        // The page shows back what was searched for.
        $filters = $request->validate(ListProductsData::rules());

        /** @var view-string $view */
        $view = 'showroom::ui.products.index';

        try {
            $products = app(ListProductsAction::class)->execute(ListProductsData::fromValidated($filters))->withQueryString();
            $error = null;
        } catch (ShowroomException $e) {
            // A search engine that is down or refuses the query still leaves
            // the page usable.
            $products = null;
            $error = $e->getMessage();
        }

        return view($view, [
            'products' => $products,
            'error' => $error,
            'filters' => $filters,
            'families' => $this->families(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeScreen('create', ProductModel::class);

        /** @var view-string $view */
        $view = 'showroom::ui.products.create';

        return view($view, ['families' => $this->families()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('create', ProductModel::class);

        $product = app(CreateProductAction::class)->execute(CreateProductData::from($request->all()));

        return redirect()
            ->route('atrium.showroom.products.show', $product)
            ->with('status', __('showroom::showroom.product_created'));
    }

    public function show(Request $request, ProductModel $product): View
    {
        $this->authorizeScreen('view', $product);

        $product = app(ShowProductAction::class)->execute($product);

        /** @var view-string $view */
        $view = 'showroom::ui.products.show';

        return view($view, [
            'product' => $product,
            'attributes' => $this->formAttributes($product, $request->query('add')),
            'slot' => EditingSlot::fromRequest($request),
            'inherited' => $product->inheritedValues(),
            'addable' => $product->settableAttributes() === null
                ? AttributeModel::query()->whereNotIn('code', array_keys($product->ownValues()))->orderBy('code')->pluck('code', 'code')->all()
                : [],
            'families' => $this->families(),
            'history' => app(ListProductVersionsAction::class)->execute($product, new ListProductVersionsData(perPage: 10)),
            'transitions' => array_values(array_filter(
                Transition::cases(),
                fn (Transition $transition): bool => in_array(
                    $product->status,
                    $transition->startsFrom(config('showroom.workflow.require_approval', true) !== false),
                    true,
                ) && ! ($transition === Transition::Unpublish && $product->published_version === null),
            )),
        ]);
    }

    public function transition(Request $request, ProductModel $product): RedirectResponse
    {
        $this->authorizeScreen('update', $product);

        try {
            app(TransitionProductAction::class)->execute($product, TransitionProductData::from($request->all()));
        } catch (ShowroomException $e) {
            return back()->withErrors(['transition' => $e->getMessage()]);
        }

        return back()->with('status', __('showroom::showroom.transitioned'));
    }

    public function revert(Request $request, ProductModel $product): RedirectResponse
    {
        $this->authorizeScreen('update', $product);

        app(RevertProductAction::class)->execute($product, RevertProductData::from($request->all()));

        return back()->with('status', __('showroom::showroom.reverted'));
    }

    public function update(Request $request, ProductModel $product): RedirectResponse
    {
        $this->authorizeScreen('update', $product);

        /** @var array<string, mixed> $input */
        $input = (array) $request->input('v', []);

        $slot = EditingSlot::fromRequest($request);
        $data = ['values' => ValueForm::toValues($input, $this->formAttributes($product, $request->input('add')), $slot)];

        if ($request->has('categories')) {
            $data['categories'] = CategoryCodes::fromForm($request->input('categories'));
        }

        if ($request->has('enabled')) {
            $data['enabled'] = $request->boolean('enabled');
        }

        if (! $product->isVariant()) {
            foreach (['family', 'owner'] as $field) {
                if ($request->has($field)) {
                    $data[$field] = $request->filled($field) ? $request->string($field)->toString() : null;
                }
            }
        }

        // The form builds the payload, so it is held to the Action's rules too.
        app(UpdateProductAction::class)->execute($product, UpdateProductData::from($data));

        return redirect()
            ->route('atrium.showroom.products.show', ['product' => $product, ...$slot->query()])
            ->with('status', __('showroom::showroom.product_updated'));
    }

    public function destroy(ProductModel $product): RedirectResponse
    {
        $this->authorizeScreen('delete', $product);

        app(DeleteProductAction::class)->execute($product);

        return redirect()
            ->route('atrium.showroom.products.index')
            ->with('status', __('showroom::showroom.product_deleted'));
    }

    /**
     * The attributes the value form shows: the settable ones, or — for a
     * product without a family — those it has values for plus one to add.
     *
     * @return Collection<int, AttributeModel>
     */
    private function formAttributes(ProductModel $product, mixed $add): Collection
    {
        $settable = $product->settableAttributes();

        if ($settable !== null) {
            return $settable->load('options');
        }

        $codes = array_keys($product->ownValues());

        if (is_string($add) && $add !== '') {
            $codes[] = $add;
        }

        return AttributeModel::query()->with('options')->whereIn('code', $codes)->orderBy('code')->get();
    }

    /**
     * @return array<string, string>
     */
    private function families(): array
    {
        return FamilyModel::query()
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (FamilyModel $family): array => [$family->code => $family->label()])
            ->all();
    }
}
