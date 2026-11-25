<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Enums;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

/**
 * The kinds of record catalog search looks through, in the order its
 * results come.
 */
enum SearchType: string
{
    case Products = 'products';
    case Assets = 'assets';
    case Categories = 'categories';
    case Owners = 'owners';
    case Families = 'families';
    case Attributes = 'attributes';
    case AttributeGroups = 'attribute_groups';

    /**
     * The types a caller asks for: the one `type` names, or every type when
     * it names none. A value that names no type also counts as every type, so
     * authorization can run before validation refuses it.
     *
     * @return list<self>
     */
    public static function requested(mixed $type): array
    {
        $named = $type instanceof self ? $type : (is_string($type) ? self::tryFrom($type) : null);

        return $named === null ? self::cases() : [$named];
    }

    /**
     * The model searched, whose `viewAny` decides who may find it.
     *
     * @return class-string<Model>
     */
    public function model(): string
    {
        return match ($this) {
            self::Products => ProductModel::class,
            self::Assets => AssetModel::class,
            self::Categories => CategoryModel::class,
            self::Owners => OwnerModel::class,
            self::Families => FamilyModel::class,
            self::Attributes => AttributeModel::class,
            self::AttributeGroups => AttributeGroupModel::class,
        };
    }
}
