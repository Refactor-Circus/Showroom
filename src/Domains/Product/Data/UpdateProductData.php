<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;

/**
 * Input for UpdateProductAction: the fields to change; fields left out are
 * kept. `values` patches only the slots sent (`data: null` clears one), and
 * `categories` and each association type named replace what was there.
 */
final readonly class UpdateProductData extends Data
{
    /**
     * @param  array<string, mixed>|null|Missing  $values
     * @param  array<int, string>|null|Missing  $categories
     * @param  array<string, mixed>|Missing  $associations
     * @param  array<string, mixed>|Missing  $quantifiedAssociations
     */
    public function __construct(
        public string|null|Missing $family = new Missing,
        public string|null|Missing $owner = new Missing,
        public bool|Missing $enabled = new Missing,
        public array|null|Missing $values = new Missing,
        public array|null|Missing $categories = new Missing,
        public array|Missing $associations = new Missing,
        public array|Missing $quantifiedAssociations = new Missing,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'identifier' => ['prohibited'],
            'parent' => ['prohibited'],
            'family' => ['sometimes', 'nullable', 'string', Rule::exists(FamilyModel::class, 'code')],
            'owner' => ['sometimes', 'nullable', 'string', Rule::exists(OwnerModel::class, 'code')],
            'enabled' => ['sometimes', 'boolean'],
            'values' => ['sometimes', 'nullable', 'array'],
            // The whole list of category codes, from any trees; replaced.
            'categories' => ['sometimes', 'nullable', 'array', 'list'],
            'categories.*' => ['string', 'distinct', Rule::exists(CategoryModel::class, 'code')],
        ] + Associations::rules();
    }
}
