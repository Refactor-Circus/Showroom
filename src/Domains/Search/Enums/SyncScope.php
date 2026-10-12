<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Search\Enums;

/**
 * A set of products whose derived data needs a sync, named so a queued job
 * can list them later: every product, or those of a family, an owner's
 * subtree or a category's. Queued jobs store the value: append, never rename.
 */
enum SyncScope: string
{
    case All = 'all';
    case Family = 'family';
    case Owner = 'owner';
    case Category = 'category';
}
