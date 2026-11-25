<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Data;

use RefactorCircus\Showroom\Domains\Catalog\Enums\SearchType;

/**
 * One record catalog search found. Showroom names the record; the caller
 * builds its own link from the type and id.
 */
final readonly class SearchHit
{
    public function __construct(
        public SearchType $type,
        public string $id,
        public string $code,
        public string $label,
        public ?string $subtitle = null,
    ) {}
}
