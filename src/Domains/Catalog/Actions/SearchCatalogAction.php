<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RefactorCircus\Keystone\Auth\Authorizer;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Catalog\Data\SearchCatalogData;
use RefactorCircus\Showroom\Domains\Catalog\Data\SearchHit;
use RefactorCircus\Showroom\Domains\Catalog\Enums\SearchType;
use RefactorCircus\Showroom\Domains\Catalog\Events\CatalogSearchedActionEvent;
use RefactorCircus\Showroom\Domains\Catalog\Events\CatalogSearchingActionEvent;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

final class SearchCatalogAction
{
    /**
     * Find records of every requested type the user may list, one query per
     * type, at most `limit` of each, in the fixed type order. With
     * authorization off every requested type is searched.
     *
     * @return Collection<int, SearchHit>
     */
    public function execute(SearchCatalogData $data, ?Authenticatable $user): Collection
    {
        CatalogSearchingActionEvent::dispatch($data);

        $hits = $this->perform($data, $user);

        CatalogSearchedActionEvent::dispatch($data, $hits);

        return $hits;
    }

    /**
     * @return Collection<int, SearchHit>
     */
    private function perform(SearchCatalogData $data, ?Authenticatable $user): Collection
    {
        $authorizer = Authorizer::for(app(PackageRegistry::class)->forOrFail(self::class));
        $hits = new Collection;

        foreach ($data->types() as $type) {
            if ($authorizer->can($user, 'viewAny', $type->model())) {
                $hits = $hits->concat($this->find($type, $data->q, $data->limit));
            }
        }

        return $hits->values();
    }

    /**
     * @return Collection<int, SearchHit>
     */
    private function find(SearchType $type, string $term, int $limit): Collection
    {
        return match ($type) {
            SearchType::Products => $this->products($term, $limit),
            SearchType::Assets => $this->assets($term, $limit),
            SearchType::Categories => CategoryModel::query()->search($term)->orderBy('code')->limit($limit)->get()
                ->map(fn (CategoryModel $category): SearchHit => new SearchHit($type, $category->id, $category->code, $category->label(), $category->code))
                ->toBase(),
            SearchType::Owners => OwnerModel::query()->with('type')->search($term)->orderBy('code')->limit($limit)->get()
                ->map(fn (OwnerModel $owner): SearchHit => new SearchHit($type, $owner->id, $owner->code, $owner->label(), $owner->code.' · '.$owner->type->code))
                ->toBase(),
            SearchType::Families => FamilyModel::query()->search($term)->orderBy('code')->limit($limit)->get()
                ->map(fn (FamilyModel $family): SearchHit => new SearchHit($type, $family->id, $family->code, $family->label(), $family->code))
                ->toBase(),
            SearchType::Attributes => AttributeModel::query()->search($term)->orderBy('code')->limit($limit)->get()
                ->map(fn (AttributeModel $attribute): SearchHit => new SearchHit($type, $attribute->id, $attribute->code, $attribute->label(), $attribute->code.' · '.$attribute->type->value))
                ->toBase(),
            SearchType::AttributeGroups => AttributeGroupModel::query()->search($term)->orderBy('code')->limit($limit)->get()
                ->map(fn (AttributeGroupModel $group): SearchHit => new SearchHit($type, $group->id, $group->code, $group->label(), $group->code))
                ->toBase(),
        };
    }

    /**
     * Products whose identifier starts with the term, by identifier. A
     * product has no label, so its identifier is both code and label.
     *
     * @return Collection<int, SearchHit>
     */
    private function products(string $term, int $limit): Collection
    {
        return ProductModel::query()
            ->where('identifier', 'like', $term.'%')
            ->orderBy('identifier')
            ->limit($limit)
            ->get()
            ->map(fn (ProductModel $product): SearchHit => new SearchHit(SearchType::Products, $product->id, $product->identifier, $product->identifier))
            ->toBase();
    }

    /**
     * Assets matching by code, label or filename, newest first, with the
     * filename beneath the label.
     *
     * @return Collection<int, SearchHit>
     */
    private function assets(string $term, int $limit): Collection
    {
        return AssetModel::query()
            ->where(fn (Builder $builder): Builder => $builder->search($term)->orWhere('filename', 'like', '%'.$term.'%'))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (AssetModel $asset): SearchHit => new SearchHit(SearchType::Assets, $asset->id, $asset->code, $asset->label(), $asset->filename))
            ->toBase();
    }
}
