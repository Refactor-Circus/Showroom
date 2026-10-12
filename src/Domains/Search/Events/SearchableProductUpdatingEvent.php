<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Search\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Showroom\Domains\Search\Models\SearchableProductModel;

/**
 * The SearchableProduct `updating` Eloquent event.
 */
final class SearchableProductUpdatingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public SearchableProductModel $product) {}

    public function model(): Model
    {
        return $this->product;
    }

    public function hook(): string
    {
        return 'updating';
    }
}
