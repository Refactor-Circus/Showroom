<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Workflow\Data\TransitionProductData;

/**
 * A product moved through the workflow.
 */
final class ProductTransitionedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ProductModel $product,
        public TransitionProductData $data,
    ) {}
}
