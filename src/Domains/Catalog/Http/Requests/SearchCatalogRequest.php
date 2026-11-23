<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Catalog\Actions\SearchCatalogAction;
use RefactorCircus\Showroom\Domains\Catalog\Data\SearchCatalogData;
use RefactorCircus\Showroom\Domains\Catalog\Enums\SearchType;
use RefactorCircus\Showroom\Domains\Catalog\Resources\CatalogSearchResultResource;

final class SearchCatalogRequest extends Request
{
    /**
     * The caller must be allowed to list at least one requested type; the
     * search then skips the types they may not list.
     */
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        foreach (SearchType::requested($this->query('type')) as $type) {
            if ($this->allows('viewAny', $type->model())) {
                return true;
            }
        }

        return false;
    }

    public function rules(): array
    {
        return SearchCatalogData::rules();
    }

    public function persist(): JsonResponse
    {
        $hits = app(SearchCatalogAction::class)->execute(SearchCatalogData::fromValidated($this->validated()), $this->user());

        return CatalogSearchResultResource::collection($hits)->response();
    }
}
