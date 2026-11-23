<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Enums;

/**
 * Where a product's working copy stands in review. Publishing is separate:
 * a product has a published version or it has not.
 */
enum ProductStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Archived = 'archived';
}
