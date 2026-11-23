<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Catalog\Actions\SearchCatalogAction;
use RefactorCircus\Showroom\Domains\Catalog\Data\SearchCatalogData;
use RefactorCircus\Showroom\Domains\Catalog\Enums\SearchType;
use RefactorCircus\Showroom\Domains\Catalog\Resources\CatalogSearchResultResource;

final class SearchCatalogMcpRequest extends Request
{
    /**
     * The caller must be allowed to list at least one requested type; the
     * search then skips the types they may not list.
     */
    protected function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        foreach (SearchType::requested($this->get('type')) as $type) {
            if ($this->allows('viewAny', $type->model())) {
                return true;
            }
        }

        return false;
    }

    protected function rules(): array
    {
        return SearchCatalogData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $hits = app(SearchCatalogAction::class)->execute(SearchCatalogData::fromValidated($validated), $this->user());

        return CatalogSearchResultResource::collection($hits)->toStructuredResponse();
    }
}
