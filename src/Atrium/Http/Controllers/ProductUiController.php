<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Showroom\Atrium\FlagScreen;
use RefactorCircus\Showroom\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Showroom\Atrium\Support\CategoryCodes;
use RefactorCircus\Showroom\Atrium\Support\EditingSlot;
use RefactorCircus\Showroom\Atrium\Support\ExternalIdForm;
use RefactorCircus\Showroom\Atrium\Support\Screens;
use RefactorCircus\Showroom\Atrium\Support\ValueForm;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Flag\Enums\FlagSubject;
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
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::list('showroom.products', ProductModel::class), $request),
            'products' => $products,
            'error' => $error,
            'filters' => $filters,
            'families' => $this->families(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeScreen('create', ProductModel::class);

        /** @var view-string $view */
        $view = 'showroom::ui.products.create';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::detail('showroom.product-create', null, ProductModel::class), $request),
            'families' => $this->families(),
        ]);
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
        $mayAssign = FlagScreen::mayAssign($product, $request);

        /** @var view-string $view */
        $view = 'showroom::ui.products.show';

        $attributes = $this->formAttributes($product, $request->query('add'));

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::detail('showroom.product', $product, ProductModel::class), $request),
            'product' => $product,
            'mayAssign' => $mayAssign,
            'flagOptions' => $mayAssign ? FlagScreen::options(FlagSubject::Product) : collect(),
            'attributes' => $attributes,
            'slot' => EditingSlot::fromRequest($request),
            'inherited' => $product->inheritedValues(),
            'addable' => $product->settableAttributes() === null
                ? AttributeModel::query()->whereNotIn('code', $attributes->pluck('code')->all())->orderBy('code')->pluck('code', 'code')->all()
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

        $externalIds = ExternalIdForm::fromForm($request);

        if ($externalIds !== null) {
            $data['external_ids'] = $externalIds;
        }

        // Sent only by those shown the own-flags field; the Action asks `assign` when they change.
        $flags = FlagScreen::fromForm($request);

        if ($flags !== null) {
            $data['flags'] = $flags;
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
     * The attributes the value form shows: a variant's settable ones, or —
     * for a simple product — its family's attributes in the family's order,
     * then the others it has values for, plus one to add, by code.
     *
     * @return Collection<int, AttributeModel>
     */
    private function formAttributes(ProductModel $product, mixed $add): Collection
    {
        $settable = $product->settableAttributes();

        if ($settable !== null) {
            return $settable->load('options');
        }

        $family = $product->family?->familyAttributes()->with('options')->get() ?? new EloquentCollection;

        $codes = array_keys($product->ownValues());

        if (is_string($add) && $add !== '') {
            $codes[] = $add;
        }

        $others = AttributeModel::query()
            ->with('options')
            ->whereIn('code', $codes)
            ->whereKeyNot($family->modelKeys())
            ->orderBy('code')
            ->get();

        return $family->concat($others);
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
