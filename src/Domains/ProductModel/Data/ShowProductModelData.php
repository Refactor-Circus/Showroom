<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Attribute\Data\ValueFilter;

/**
 * Input for ShowProductModelAction: optionally, only one channel's (`scope`) and
 * some locales' values.
 */
final readonly class ShowProductModelData extends Data
{
    /**
     * @param  list<string>|null  $locales
     */
    public function __construct(
        public ?string $scope = null,
        public ?array $locales = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return ValueFilter::rules();
    }

    public function valueFilter(): ValueFilter
    {
        return new ValueFilter($this->scope, $this->locales);
    }
}
