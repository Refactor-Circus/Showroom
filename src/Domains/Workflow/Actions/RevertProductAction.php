<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Actions;

use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationModel;
use RefactorCircus\Showroom\Domains\Attribute\Services\Values;
use RefactorCircus\Showroom\Domains\Product\Actions\UpdateProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\UpdateProductData;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Workflow\Data\RevertProductData;
use RefactorCircus\Showroom\Domains\Workflow\Events\ProductRevertedActionEvent;
use RefactorCircus\Showroom\Domains\Workflow\Events\ProductRevertingActionEvent;
use RefactorCircus\Showroom\Domains\Workflow\Services\Versions;

final class RevertProductAction
{
    public function __construct(private readonly Versions $versions) {}

    /**
     * Restore what a version held — own values, categories, associations,
     * family, owner and enabled flag — as a new `reverted` version. Status
     * and publishing are the workflow's, and stay as they are.
     *
     * @throws ValidationException
     */
    public function execute(ProductModel $product, RevertProductData $data): ProductModel
    {
        ProductRevertingActionEvent::dispatch($product, $data);

        $result = $this->perform($product, $data);

        ProductRevertedActionEvent::dispatch($result, $data);

        return $result;
    }

    private function perform(ProductModel $product, RevertProductData $data): ProductModel
    {
        $number = $data->version;
        $version = $this->versions->find($product, $number);

        if ($version === null) {
            throw ValidationException::withMessages(['version' => sprintf('Product "%s" has no version %d.', $product->identifier, $number)]);
        }

        $snapshot = $version->snapshot;
        $payload = [
            'enabled' => (bool) ($snapshot['enabled'] ?? true),
            'values' => $this->valuesPatch($product, is_array($snapshot['values'] ?? null) ? $snapshot['values'] : []),
            'categories' => is_array($snapshot['categories'] ?? null) ? $snapshot['categories'] : [],
            ...$this->associationsPayload($product, $snapshot),
        ];

        if (! $product->isVariant()) {
            $payload['family'] = $snapshot['family'] ?? null;
            $payload['owner'] = $snapshot['owner'] ?? null;
        }

        $comment = $data->comment ?? sprintf('Reverted to version %d.', $number);

        return $this->versions->as('reverted', $comment, fn (): ProductModel => app(UpdateProductAction::class)->execute(
            $product,
            // The snapshot held what the product could hold then: check it still can.
            UpdateProductData::from($payload),
        ));
    }

    /**
     * The snapshot's slots, plus a clearing slot for everything the product
     * holds now that the snapshot did not.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, array<int, array{locale: mixed, scope: mixed, data: mixed}>>
     */
    private function valuesPatch(ProductModel $product, array $snapshot): array
    {
        $patch = [];

        foreach (Values::toStandard($product->ownValues()) as $code => $slots) {
            foreach ($slots as $slot) {
                $patch[$code][] = ['locale' => $slot['locale'], 'scope' => $slot['scope'], 'data' => null];
            }
        }

        foreach ($snapshot as $code => $slots) {
            foreach (is_array($slots) ? $slots : [] as $slot) {
                if (! is_array($slot)) {
                    continue;
                }

                // Replace a clearing slot for the same locale and scope.
                $patch[$code] = array_values(array_filter(
                    $patch[$code] ?? [],
                    fn (array $existing): bool => $existing['locale'] !== ($slot['locale'] ?? null) || $existing['scope'] !== ($slot['scope'] ?? null),
                ));
                $patch[$code][] = ['locale' => $slot['locale'] ?? null, 'scope' => $slot['scope'] ?? null, 'data' => $slot['data'] ?? null];
            }
        }

        return $patch;
    }

    /**
     * Every type the product uses now or the snapshot used, set to the
     * snapshot's targets — empty where it had none.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array{associations: array<string, mixed>, quantified_associations: array<string, mixed>}
     */
    private function associationsPayload(ProductModel $product, array $snapshot): array
    {
        $payload = ['associations' => [], 'quantified_associations' => []];

        foreach ($product->associations()->with('type')->get() as $association) {
            /** @var AssociationModel $association */
            $key = $association->type->is_quantified ? 'quantified_associations' : 'associations';
            $payload[$key][$association->type->code] = ['products' => [], 'product_models' => []];
        }

        foreach (['associations', 'quantified_associations'] as $key) {
            foreach (is_array($snapshot[$key] ?? null) ? $snapshot[$key] : [] as $code => $groups) {
                $payload[$key][$code] = $groups;
            }
        }

        return $payload;
    }
}
