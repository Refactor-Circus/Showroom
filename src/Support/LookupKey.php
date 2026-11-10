<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Support;

/**
 * The column a product or owner is found by in URLs, MCP tool arguments and
 * the actions that take one record: `showroom.lookup.products` and
 * `showroom.lookup.owners`, or a call's own `by`.
 *
 * An identifier or owner code is never a lookup key: it stays an input
 * reference only (`parent`, `owner`, associations, filters).
 */
enum LookupKey: string
{
    /** The record's ULID. */
    case Id = 'id';

    /** The record's slug, set once when it is created. */
    case Slug = 'slug';
}
