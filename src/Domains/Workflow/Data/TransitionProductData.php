<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Workflow\Enums\Transition;

/**
 * Input for TransitionProductAction: the move through the workflow, and why.
 * `publish` makes the product's current version the one storefronts read;
 * the working copy stays editable.
 */
final readonly class TransitionProductData extends Data
{
    public function __construct(
        public Transition $transition,
        public ?string $comment = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'transition' => ['required', 'string', Rule::enum(Transition::class)],
            'comment' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
