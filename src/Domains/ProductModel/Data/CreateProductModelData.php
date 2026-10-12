<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;

/**
 * Input for CreateProductModelAction. A root model names its family variant;
 * a sub-model (level 1 of a two-level variant) names its parent and takes the
 * parent's variant. `values` are keyed by attribute code, in standard shape.
 */
final readonly class CreateProductModelData extends Data
{
    /**
     * @param  array<string, mixed>|null  $values
     * @param  array<int, string>|null  $categories
     * @param  array<string, mixed>|null  $associations
     * @param  array<string, mixed>|null  $quantifiedAssociations
     */
    public function __construct(
        public string $code,
        public ?string $familyVariant = null,
        public ?string $parent = null,
        public ?string $owner = null,
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
            'code' => ['required', 'string', 'max:191', 'regex:/^[A-Za-z0-9][A-Za-z0-9_.-]*$/', Rule::unique(ProductModelModel::class, 'code')],
            'family_variant' => ['required_without:parent', 'nullable', 'string', Rule::exists(FamilyVariantModel::class, 'code')],
            'parent' => ['sometimes', 'nullable', 'string', Rule::exists(ProductModelModel::class, 'code')],
            'owner' => ['sometimes', 'nullable', 'string', Rule::exists(OwnerModel::class, 'code')],
            'values' => ['sometimes', 'nullable', 'array'],
            // The whole list of category codes, from any trees.
            'categories' => ['sometimes', 'nullable', 'array', 'list'],
            'categories.*' => ['string', 'distinct', Rule::exists(CategoryModel::class, 'code')],
        ] + Associations::rules();
    }
}
