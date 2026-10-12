<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;

/**
 * Input for CreateProductAction. `values` are keyed by attribute code, in
 * standard shape: `{"name": [{"locale": null, "scope": null, "data": "Tee"}]}`.
 * A variant product names its `parent` product model and takes its family.
 */
final readonly class CreateProductData extends Data
{
    /**
     * @param  array<string, mixed>|null  $values
     * @param  array<int, string>|null  $categories
     * @param  array<string, mixed>|null  $associations
     * @param  array<string, mixed>|null  $quantifiedAssociations
     */
    public function __construct(
        public string $identifier,
        public ?string $family = null,
        public ?string $parent = null,
        public ?string $owner = null,
        public bool $enabled = true,
        public ?array $values = null,
        public ?array $categories = null,
        public ?array $associations = null,
        public ?array $quantifiedAssociations = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:191', 'regex:/^[A-Za-z0-9][A-Za-z0-9_.-]*$/', Rule::unique(ProductModel::class, 'identifier')],
            'family' => ['sometimes', 'nullable', 'string', Rule::exists(FamilyModel::class, 'code')],
            'parent' => ['sometimes', 'nullable', 'string', Rule::exists(ProductModelModel::class, 'code')],
            'owner' => ['sometimes', 'nullable', 'string', Rule::exists(OwnerModel::class, 'code')],
            'enabled' => ['sometimes', 'boolean'],
            'values' => ['sometimes', 'nullable', 'array'],
            // The whole list of category codes, from any trees.
            'categories' => ['sometimes', 'nullable', 'array', 'list'],
            'categories.*' => ['string', 'distinct', Rule::exists(CategoryModel::class, 'code')],
        ] + Associations::rules();
    }
}
