<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Data;

use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
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
     * @param  array<string, string|null>|null  $externalIds  The product's ids in other systems, {system: id}.
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
        public ?array $externalIds = null,
    ) {}

    /**
     * The identifier pattern used when `showroom.products.identifier_pattern`
     * is absent: any printable characters, up to 191, with no leading or
     * trailing whitespace and no control characters. `D` keeps `$` from
     * matching before a trailing newline.
     */
    private const string IDENTIFIER_PATTERN = '/^[^\s\p{C}](?:[^\p{C}]{0,189}[^\s\p{C}])?$/uD';

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:191', Rule::regex(self::identifierPattern()), Rule::unique(ProductModel::class, 'identifier')],
            // Set from the identifier; read-only.
            'slug' => ['prohibited'],
            'family' => ['sometimes', 'nullable', 'string', Rule::exists(FamilyModel::class, 'code')],
            'parent' => ['sometimes', 'nullable', 'string', Rule::exists(ProductModelModel::class, 'code')],
            'owner' => ['sometimes', 'nullable', 'string', Rule::exists(OwnerModel::class, 'code')],
            'enabled' => ['sometimes', 'boolean'],
            'values' => ['sometimes', 'nullable', 'array'],
            // The whole list of category codes, from any trees.
            'categories' => ['sometimes', 'nullable', 'array', 'list'],
            'categories.*' => ['string', 'distinct', Rule::exists(CategoryModel::class, 'code')],
        ] + Associations::rules() + ExternalIds::rules();
    }

    /**
     * The pattern every identifier must match, from
     * `showroom.products.identifier_pattern`. A pattern that does not compile
     * throws here, naming the key, instead of warning on every validation.
     *
     * @throws InvalidArgumentException
     */
    private static function identifierPattern(): string
    {
        $pattern = config('showroom.products.identifier_pattern');
        $pattern = is_string($pattern) && $pattern !== '' ? $pattern : self::IDENTIFIER_PATTERN;

        error_clear_last();

        if (@preg_match($pattern, '') === false) {
            throw new InvalidArgumentException(sprintf(
                'The showroom.products.identifier_pattern [%s] is not a valid regular expression: %s',
                $pattern,
                error_get_last()['message'] ?? preg_last_error_msg(),
            ));
        }

        return $pattern;
    }
}
