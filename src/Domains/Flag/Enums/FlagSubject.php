<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Enums;

use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

/**
 * A kind of record a flag may be assigned to, as named in a flag's
 * `applies_to`. Flags store the value: append, never rename.
 */
enum FlagSubject: string
{
    case Product = 'product';
    case Owner = 'owner';

    /**
     * Every value, in declaration order.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $subject): string => $subject->value, self::cases());
    }

    /**
     * The value stored in an assignment's `subject_type` for this kind of record.
     */
    public function morphClass(): string
    {
        return match ($this) {
            self::Product => (new ProductModel)->getMorphClass(),
            self::Owner => (new OwnerModel)->getMorphClass(),
        };
    }
}
