<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavGroup;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Atrium\Domains\Plugins\Support\Plugin;
use RefactorCircus\Atrium\Domains\Search\Data\SearchResult;
use RefactorCircus\Atrium\Domains\Search\Data\SearchSource;
use RefactorCircus\Atrium\Domains\Settings\Data\SettingsPanel;
use RefactorCircus\Atrium\Domains\Widgets\Data\WidgetDefinition;
use RefactorCircus\Atrium\Support\Icons;
use RefactorCircus\Keystone\Auth\Authorizer;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Showroom\Atrium\Http\Controllers\AssetUiController;
use RefactorCircus\Showroom\Atrium\Http\Controllers\AssociationUiController;
use RefactorCircus\Showroom\Atrium\Http\Controllers\AttributeGroupUiController;
use RefactorCircus\Showroom\Atrium\Http\Controllers\AttributeUiController;
use RefactorCircus\Showroom\Atrium\Http\Controllers\CategoryUiController;
use RefactorCircus\Showroom\Atrium\Http\Controllers\ChannelUiController;
use RefactorCircus\Showroom\Atrium\Http\Controllers\FamilyUiController;
use RefactorCircus\Showroom\Atrium\Http\Controllers\FamilyVariantUiController;
use RefactorCircus\Showroom\Atrium\Http\Controllers\OwnerTypeUiController;
use RefactorCircus\Showroom\Atrium\Http\Controllers\OwnerUiController;
use RefactorCircus\Showroom\Atrium\Http\Controllers\ProductModelUiController;
use RefactorCircus\Showroom\Atrium\Http\Controllers\ProductUiController;
use RefactorCircus\Showroom\Atrium\Http\Controllers\TransferUiController;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Enums\ProductStatus;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\CompletenessModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;
use RefactorCircus\Showroom\Support\Tables;

/**
 * Registers Showroom inside the Atrium dashboard.
 */
class ShowroomPlugin extends Plugin
{
    /**
     * The records search looks through.
     *
     * @var array<int, class-string<Model>>
     */
    private const array SEARCHED = [ProductModel::class, AssetModel::class, CategoryModel::class, OwnerModel::class, FamilyModel::class, AttributeModel::class, AttributeGroupModel::class];

    /**
     * Features from `showroom.atrium.features` that switch Showroom in Atrium
     * on and off as a whole. A feature class that is not installed, such as
     * ShowroomSupportFeature without refactor-circus/pennantplus, is skipped.
     *
     * @return array<int, string>
     */
    public function features(): array
    {
        return $this->featuresFromConfig('showroom.atrium.features');
    }

    /**
     * The package's section in the sidebar rail: its icon and its place.
     */
    public function navigationGroups(): array
    {
        return [
            NavGroup::make(__('showroom::showroom.catalog'))->icon(Icons::svg('cube'))->sort(20),
        ];
    }

    /**
     * Each item shows only to those who may open its page: the policy
     * ability its list asks, as the JSON API asks it.
     */
    public function navigation(): array
    {
        $group = __('showroom::showroom.catalog');

        return [
            NavItem::make(__('showroom::showroom.products'))->icon(Icons::svg('cube'))->route('atrium.showroom.products.index')->group($group)->sort(1)
                ->authorize(fn (Request $request): bool => ScreenAccess::allows('viewAny', ProductModel::class, $request)),
            NavItem::make(__('showroom::showroom.product_models'))->icon(Icons::svg('square-3-stack-3d'))->route('atrium.showroom.product-models.index')->group($group)->sort(2)
                ->authorize(fn (Request $request): bool => ScreenAccess::allows('viewAny', ProductModelModel::class, $request)),
            NavItem::make(__('showroom::showroom.categories'))->icon(Icons::svg('folder'))->route('atrium.showroom.categories.index')->group($group)->sort(3)
                ->authorize(fn (Request $request): bool => ScreenAccess::allows('viewAny', CategoryModel::class, $request)),
            NavItem::make(__('showroom::showroom.assets'))->icon(Icons::svg('photo'))->route('atrium.showroom.assets.index')->group($group)->sort(4)
                ->authorize(fn (Request $request): bool => ScreenAccess::allows('viewAny', AssetModel::class, $request)),
            NavItem::make(__('showroom::showroom.owners'))->icon(Icons::svg('building-storefront'))->route('atrium.showroom.owners.index')->group($group)->sort(3)
                ->authorize(fn (Request $request): bool => ScreenAccess::allows('viewAny', OwnerModel::class, $request)),
            NavItem::make(__('showroom::showroom.families'))->icon(Icons::svg('rectangle-group'))->route('atrium.showroom.families.index')->group($group)->sort(5)
                ->authorize(fn (Request $request): bool => ScreenAccess::allows('viewAny', FamilyModel::class, $request)),
            NavItem::make(__('showroom::showroom.attributes'))->icon(Icons::svg('tag'))->route('atrium.showroom.attributes.index')->group($group)->sort(10)
                ->authorize(fn (Request $request): bool => ScreenAccess::allows('viewAny', AttributeModel::class, $request)),
            NavItem::make(__('showroom::showroom.channels'))->icon(Icons::svg('globe-alt'))->route('atrium.showroom.channels.index')->group($group)->sort(30)
                ->authorize(fn (Request $request): bool => ScreenAccess::allows('viewAny', ChannelModel::class, $request)),
            NavItem::make(__('showroom::showroom.transfers'))->icon(Icons::svg('arrows-up-down'))->route('atrium.showroom.transfers.index')->group($group)->sort(40)
                ->authorize(fn (Request $request): bool => ScreenAccess::transfers($request)),
            NavItem::make(__('showroom::showroom.association_types'))->icon(Icons::svg('link'))->route('atrium.showroom.association-types.index')->group($group)->sort(35)
                ->authorize(fn (Request $request): bool => ScreenAccess::allows('viewAny', AssociationTypeModel::class, $request)),
            NavItem::make(__('showroom::showroom.attribute_groups'))->icon(Icons::svg('squares-2x2'))->route('atrium.showroom.attribute-groups.index')->group($group)->sort(20)
                ->authorize(fn (Request $request): bool => ScreenAccess::allows('viewAny', AttributeGroupModel::class, $request)),
        ];
    }

    public function routes(): void
    {
        Route::name('showroom.')->group(function (): void {
            Route::get('showroom/products', [ProductUiController::class, 'index'])->name('products.index');
            Route::get('showroom/products/create', [ProductUiController::class, 'create'])->name('products.create');
            Route::post('showroom/products', [ProductUiController::class, 'store'])->name('products.store');
            Route::get('showroom/products/{product:identifier}', [ProductUiController::class, 'show'])->name('products.show');
            Route::patch('showroom/products/{product:identifier}', [ProductUiController::class, 'update'])->name('products.update');
            Route::delete('showroom/products/{product:identifier}', [ProductUiController::class, 'destroy'])->name('products.destroy');
            Route::post('showroom/products/{product:identifier}/transitions', [ProductUiController::class, 'transition'])->name('products.transition');
            Route::post('showroom/products/{product:identifier}/revert', [ProductUiController::class, 'revert'])->name('products.revert');

            Route::get('showroom/product-models', [ProductModelUiController::class, 'index'])->name('product-models.index');
            Route::post('showroom/product-models', [ProductModelUiController::class, 'store'])->name('product-models.store');
            Route::get('showroom/product-models/{productModel:code}', [ProductModelUiController::class, 'show'])->name('product-models.show');
            Route::patch('showroom/product-models/{productModel:code}', [ProductModelUiController::class, 'update'])->name('product-models.update');
            Route::delete('showroom/product-models/{productModel:code}', [ProductModelUiController::class, 'destroy'])->name('product-models.destroy');

            Route::get('showroom/transfers', [TransferUiController::class, 'index'])->name('transfers.index');
            Route::post('showroom/transfers/import', [TransferUiController::class, 'import'])->name('transfers.import');
            Route::post('showroom/transfers/export', [TransferUiController::class, 'export'])->name('transfers.export');

            Route::get('showroom/association-types', [AssociationUiController::class, 'index'])->name('association-types.index');
            Route::post('showroom/association-types', [AssociationUiController::class, 'store'])->name('association-types.store');
            Route::delete('showroom/association-types/{associationType:code}', [AssociationUiController::class, 'destroy'])->name('association-types.destroy');
            Route::post('showroom/associations', [AssociationUiController::class, 'add'])->name('associations.add');
            Route::delete('showroom/associations', [AssociationUiController::class, 'remove'])->name('associations.remove');

            Route::get('showroom/channels', [ChannelUiController::class, 'index'])->name('channels.index');
            Route::post('showroom/channels', [ChannelUiController::class, 'store'])->name('channels.store');
            Route::get('showroom/channels/{channel:code}', [ChannelUiController::class, 'show'])->name('channels.show');
            Route::patch('showroom/channels/{channel:code}', [ChannelUiController::class, 'update'])->name('channels.update');
            Route::delete('showroom/channels/{channel:code}', [ChannelUiController::class, 'destroy'])->name('channels.destroy');
            Route::post('showroom/locales', [ChannelUiController::class, 'storeLocale'])->name('locales.store');
            Route::delete('showroom/locales/{locale:code}', [ChannelUiController::class, 'destroyLocale'])->name('locales.destroy');

            Route::get('showroom/assets', [AssetUiController::class, 'index'])->name('assets.index');
            Route::post('showroom/assets', [AssetUiController::class, 'store'])->name('assets.store');
            Route::post('showroom/assets/upload', [AssetUiController::class, 'upload'])->name('assets.upload');
            Route::get('showroom/assets/{asset:code}', [AssetUiController::class, 'show'])->name('assets.show');
            Route::patch('showroom/assets/{asset:code}', [AssetUiController::class, 'update'])->name('assets.update');
            Route::delete('showroom/assets/{asset:code}', [AssetUiController::class, 'destroy'])->name('assets.destroy');
            Route::post('showroom/assets/{asset:code}/links', [AssetUiController::class, 'attach'])->name('assets.attach');
            Route::delete('showroom/assets/{asset:code}/links', [AssetUiController::class, 'detach'])->name('assets.detach');

            Route::get('showroom/categories', [CategoryUiController::class, 'index'])->name('categories.index');
            Route::post('showroom/categories', [CategoryUiController::class, 'store'])->name('categories.store');
            Route::get('showroom/categories/{category:code}', [CategoryUiController::class, 'show'])->name('categories.show');
            Route::patch('showroom/categories/{category:code}', [CategoryUiController::class, 'update'])->name('categories.update');
            Route::delete('showroom/categories/{category:code}', [CategoryUiController::class, 'destroy'])->name('categories.destroy');

            Route::get('showroom/owner-types', [OwnerTypeUiController::class, 'index'])->name('owner-types.index');
            Route::post('showroom/owner-types', [OwnerTypeUiController::class, 'store'])->name('owner-types.store');
            Route::get('showroom/owner-types/{ownerType:code}', [OwnerTypeUiController::class, 'show'])->name('owner-types.show');
            Route::patch('showroom/owner-types/{ownerType:code}', [OwnerTypeUiController::class, 'update'])->name('owner-types.update');
            Route::delete('showroom/owner-types/{ownerType:code}', [OwnerTypeUiController::class, 'destroy'])->name('owner-types.destroy');

            Route::get('showroom/owners', [OwnerUiController::class, 'index'])->name('owners.index');
            Route::post('showroom/owners', [OwnerUiController::class, 'store'])->name('owners.store');
            Route::get('showroom/owners/{owner:code}', [OwnerUiController::class, 'show'])->name('owners.show');
            Route::patch('showroom/owners/{owner:code}', [OwnerUiController::class, 'update'])->name('owners.update');
            Route::delete('showroom/owners/{owner:code}', [OwnerUiController::class, 'destroy'])->name('owners.destroy');

            Route::post('showroom/family-variants', [FamilyVariantUiController::class, 'store'])->name('family-variants.store');
            Route::get('showroom/family-variants/{familyVariant:code}', [FamilyVariantUiController::class, 'show'])->name('family-variants.show');
            Route::delete('showroom/family-variants/{familyVariant:code}', [FamilyVariantUiController::class, 'destroy'])->name('family-variants.destroy');

            Route::get('showroom/families', [FamilyUiController::class, 'index'])->name('families.index');
            Route::post('showroom/families', [FamilyUiController::class, 'store'])->name('families.store');
            Route::get('showroom/families/{family:code}', [FamilyUiController::class, 'show'])->name('families.show');
            Route::patch('showroom/families/{family:code}', [FamilyUiController::class, 'update'])->name('families.update');
            Route::delete('showroom/families/{family:code}', [FamilyUiController::class, 'destroy'])->name('families.destroy');

            Route::get('showroom/attribute-groups', [AttributeGroupUiController::class, 'index'])->name('attribute-groups.index');
            Route::post('showroom/attribute-groups', [AttributeGroupUiController::class, 'store'])->name('attribute-groups.store');
            Route::get('showroom/attribute-groups/{group:code}', [AttributeGroupUiController::class, 'show'])->name('attribute-groups.show');
            Route::patch('showroom/attribute-groups/{group:code}', [AttributeGroupUiController::class, 'update'])->name('attribute-groups.update');
            Route::delete('showroom/attribute-groups/{group:code}', [AttributeGroupUiController::class, 'destroy'])->name('attribute-groups.destroy');

            Route::get('showroom/attributes', [AttributeUiController::class, 'index'])->name('attributes.index');
            Route::get('showroom/attributes/create', [AttributeUiController::class, 'create'])->name('attributes.create');
            Route::post('showroom/attributes', [AttributeUiController::class, 'store'])->name('attributes.store');
            Route::get('showroom/attributes/{attribute:code}', [AttributeUiController::class, 'show'])->name('attributes.show');
            Route::patch('showroom/attributes/{attribute:code}', [AttributeUiController::class, 'update'])->name('attributes.update');
            Route::delete('showroom/attributes/{attribute:code}', [AttributeUiController::class, 'destroy'])->name('attributes.destroy');

            Route::scopeBindings()->group(function (): void {
                Route::post('showroom/attributes/{attribute:code}/options', [AttributeUiController::class, 'storeOption'])->name('attributes.options.store');
                Route::delete('showroom/attributes/{attribute:code}/options/{option:code}', [AttributeUiController::class, 'destroyOption'])->name('attributes.options.destroy');
            });
        });
    }

    /**
     * Widget types Showroom makes available.
     *
     * Returning a definition offers the widget in the picker; it does not
     * place it on anyone's dashboard.
     */
    public function widgets(): array
    {
        return [
            WidgetDefinition::make('showroom.product-status')
                ->label(__('showroom::showroom.widget_product_status'))
                ->description(__('showroom::showroom.widget_product_status_description'))
                ->defaultSize(6, 1)
                ->view('showroom::ui.widgets.product-status')
                ->authorize(fn (Request $request): bool => ScreenAccess::allows('viewAny', ProductModel::class, $request))
                ->resolve(fn (): array => $this->statusCounts()),

            WidgetDefinition::make('showroom.completeness')
                ->label(__('showroom::showroom.widget_completeness'))
                ->description(__('showroom::showroom.widget_completeness_description'))
                ->defaultSize(6, 2)
                ->view('showroom::ui.widgets.completeness')
                ->authorize(fn (Request $request): bool => ScreenAccess::allows('viewAny', ProductModel::class, $request))
                ->resolve(fn (): array => [
                    // A scan of every score: kept briefly, as dashboards reload often.
                    'rows' => $this->remember('completeness', fn (): array => $this->completenessRows()),
                ]),

            WidgetDefinition::make('showroom.review-queue')
                ->label(__('showroom::showroom.widget_review_queue'))
                ->description(__('showroom::showroom.widget_review_queue_description'))
                ->defaultSize(6, 2)
                ->view('showroom::ui.widgets.review-queue')
                ->authorize(fn (Request $request): bool => ScreenAccess::allows('viewAny', ProductModel::class, $request))
                ->resolve(fn (): array => [
                    'products' => ProductModel::query()
                        ->where('status', ProductStatus::InReview)
                        ->oldest('updated_at')
                        ->limit(5)
                        ->get(),
                    'waiting' => ProductModel::query()->where('status', ProductStatus::InReview)->count(),
                ]),

            WidgetDefinition::make('showroom.recent-changes')
                ->label(__('showroom::showroom.widget_recent_changes'))
                ->description(__('showroom::showroom.widget_recent_changes_description'))
                ->defaultSize(6, 2)
                ->view('showroom::ui.widgets.recent-changes')
                ->authorize(fn (Request $request): bool => ScreenAccess::allows('viewAny', ProductModel::class, $request))
                ->resolve(fn (): array => [
                    'versions' => VersionModel::query()
                        ->with(['versionable', 'author'])
                        ->latest('created_at')
                        ->limit(8)
                        ->get(),
                ]),
        ];
    }

    public function settings(): ?SettingsPanel
    {
        return SettingsPanel::make('showroom')
            ->label(__('showroom::showroom.settings_label'))
            ->description(__('showroom::showroom.settings_description'))
            ->view('showroom::ui.settings')
            ->resolve(fn (): array => [
                'authorization' => config('showroom.authorization') === true,
                'routes' => (array) config('showroom.routes', []),
                'mcp' => (array) config('showroom.mcp', []),
                'pagination' => (array) config('showroom.pagination', []),
            ]);
    }

    public function search(): ?SearchSource
    {
        return SearchSource::make('showroom')
            ->label(__('showroom::showroom.label'))
            // Each kind of record is searched only for those who may list it.
            ->authorize(fn (Request $request): bool => self::searchable($request))
            ->using(fn (string $query): array => [
                ...(self::mayFind(ProductModel::class)
                    ? ProductModel::query()
                        ->where('identifier', 'like', $query.'%')
                        ->orderBy('identifier')
                        ->limit(5)
                        ->get()
                        ->map(fn (ProductModel $product): SearchResult => SearchResult::make(
                            $product->identifier,
                            route('atrium.showroom.products.show', $product),
                        )->group(__('showroom::showroom.products')))
                        ->all()
                    : []),
                ...(self::mayFind(AssetModel::class)
                    ? AssetModel::query()
                        ->where(fn (Builder $builder): Builder => $builder->search($query)->orWhere('filename', 'like', '%'.$query.'%'))
                        ->orderByDesc('created_at')
                        ->limit(5)
                        ->get()
                        ->map(fn (AssetModel $asset): SearchResult => SearchResult::make(
                            $asset->label(),
                            route('atrium.showroom.assets.show', $asset),
                        )->subtitle($asset->filename)->group(__('showroom::showroom.assets')))
                        ->all()
                    : []),
                ...(self::mayFind(CategoryModel::class)
                    ? CategoryModel::query()
                        ->search($query)
                        ->orderBy('code')
                        ->limit(5)
                        ->get()
                        ->map(fn (CategoryModel $category): SearchResult => SearchResult::make(
                            $category->label(),
                            route('atrium.showroom.categories.show', $category),
                        )->subtitle($category->code)->group(__('showroom::showroom.categories')))
                        ->all()
                    : []),
                ...(self::mayFind(OwnerModel::class)
                    ? OwnerModel::query()
                        ->with('type')
                        ->search($query)
                        ->orderBy('code')
                        ->limit(5)
                        ->get()
                        ->map(fn (OwnerModel $owner): SearchResult => SearchResult::make(
                            $owner->label(),
                            route('atrium.showroom.owners.show', $owner),
                        )->subtitle($owner->code.' · '.$owner->type->code)->group(__('showroom::showroom.owners')))
                        ->all()
                    : []),
                ...(self::mayFind(FamilyModel::class)
                    ? FamilyModel::query()
                        ->search($query)
                        ->orderBy('code')
                        ->limit(5)
                        ->get()
                        ->map(fn (FamilyModel $family): SearchResult => SearchResult::make(
                            $family->label(),
                            route('atrium.showroom.families.show', $family),
                        )->subtitle($family->code)->group(__('showroom::showroom.families')))
                        ->all()
                    : []),
                ...(self::mayFind(AttributeModel::class)
                    ? AttributeModel::query()
                        ->search($query)
                        ->orderBy('code')
                        ->limit(5)
                        ->get()
                        ->map(fn (AttributeModel $attribute): SearchResult => SearchResult::make(
                            $attribute->label(),
                            route('atrium.showroom.attributes.show', $attribute),
                        )->subtitle($attribute->code.' · '.$attribute->type->value)->group(__('showroom::showroom.attributes')))
                        ->all()
                    : []),
                ...(self::mayFind(AttributeGroupModel::class)
                    ? AttributeGroupModel::query()
                        ->search($query)
                        ->orderBy('code')
                        ->limit(5)
                        ->get()
                        ->map(fn (AttributeGroupModel $group): SearchResult => SearchResult::make(
                            $group->label(),
                            route('atrium.showroom.attribute-groups.show', $group),
                        )->subtitle($group->code)->group(__('showroom::showroom.attribute_groups')))
                        ->all()
                    : []),
            ]);
    }

    /**
     * Whether the user may list any of the records search looks through.
     */
    private static function searchable(Request $request): bool
    {
        foreach (self::SEARCHED as $model) {
            if (ScreenAccess::allows('viewAny', $model, $request)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the user searching may list a kind of record. Search may run in
     * another process, where Atrium signs the searcher in but no request
     * carries them, so the user comes from the guard.
     *
     * @param  class-string<Model>  $model
     */
    private static function mayFind(string $model): bool
    {
        $user = auth()->user();

        return Authorizer::for(app(PackageRegistry::class)->get('showroom'))->can($user, 'viewAny', $model);
    }

    /**
     * Products per status, and how many are live, in one query.
     *
     * @return array{counts: array<string, int>, published: int}
     */
    private function statusCounts(): array
    {
        $rows = ProductModel::query()
            ->toBase()
            ->select('status')
            ->selectRaw('count(*) as products, sum(case when published_version is null then 0 else 1 end) as live')
            ->groupBy('status')
            ->get()
            ->keyBy(fn (object $row): string => (string) data_get($row, 'status'));

        return [
            'counts' => collect(ProductStatus::cases())
                ->mapWithKeys(fn (ProductStatus $status): array => [$status->value => (int) data_get($rows->get($status->value), 'products', 0)])
                ->all(),
            'published' => (int) $rows->sum(fn (object $row): int => (int) data_get($row, 'live', 0)),
        ];
    }

    /**
     * Completeness per channel and locale.
     *
     * @return array<int, array{scope: string, locale: string, products: int, complete: int, average: int}>
     */
    private function completenessRows(): array
    {
        return CompletenessModel::query()
            ->join(Tables::name('channels'), Tables::name('channels').'.id', '=', Tables::name('product_completeness').'.channel_id')
            ->join(Tables::name('locales'), Tables::name('locales').'.id', '=', Tables::name('product_completeness').'.locale_id')
            ->groupBy(Tables::name('channels').'.code', Tables::name('locales').'.code')
            ->orderBy(Tables::name('channels').'.code')
            ->orderBy(Tables::name('locales').'.code')
            ->select([Tables::name('channels').'.code as scope', Tables::name('locales').'.code as locale'])
            ->selectRaw('count(*) as products, sum(case when ratio = 100 then 1 else 0 end) as complete, avg(ratio) as average')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'scope' => (string) data_get($row, 'scope'),
                'locale' => (string) data_get($row, 'locale'),
                'products' => (int) data_get($row, 'products'),
                'complete' => (int) data_get($row, 'complete'),
                'average' => (int) round((float) data_get($row, 'average')),
            ])
            ->all();
    }

    /**
     * A widget's figures, cached for `showroom.atrium.widget_cache_seconds`
     * (0 reads them fresh every time).
     *
     * @template TValue
     *
     * @param  Closure(): TValue  $resolve
     * @return TValue
     */
    private function remember(string $key, Closure $resolve): mixed
    {
        $seconds = (int) config('showroom.atrium.widget_cache_seconds', 60);

        return $seconds > 0 ? Cache::remember('showroom:widgets:'.$key, $seconds, $resolve) : $resolve();
    }
}
