<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Concerns;

use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Family\Data\FamilyAttributeData;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;

/**
 * Membership and label rules shared by creating and updating a family.
 */
trait WritesFamilyAttributes
{
    /**
     * Replace the family's attributes with the given list, in its order
     * unless a member sets its own sort order.
     *
     * @param  array<int, FamilyAttributeData>  $members
     */
    private function replaceAttributes(FamilyModel $family, array $members): void
    {
        $ids = AttributeModel::query()
            ->whereIn('code', array_map(fn (FamilyAttributeData $member): string => $member->attribute, $members))
            ->pluck('id', 'code');

        $sync = [];

        foreach (array_values($members) as $position => $member) {
            if (! $ids->has($member->attribute)) {
                continue;
            }

            $sync[(string) $ids->get($member->attribute)] = [
                'is_required' => $member->isRequired,
                'sort_order' => $member->sortOrder ?? $position,
                'required_channels' => $member->requiredChannels !== null
                    ? json_encode($member->requiredChannels, JSON_THROW_ON_ERROR)
                    : null,
            ];
        }

        $family->familyAttributes()->sync($sync);
    }

    /**
     * Refuse to drop an attribute a family variant places on a level.
     *
     * @param  array<int, FamilyAttributeData>  $members
     *
     * @throws ValidationException
     */
    private function keepVariantAttributes(FamilyModel $family, array $members): void
    {
        $kept = array_map(fn (FamilyAttributeData $member): string => $member->attribute, $members);

        foreach ($family->variants()->with('variantAttributes')->get() as $variant) {
            foreach ($variant->variantAttributes as $attribute) {
                if (! in_array($attribute->code, $kept, true)) {
                    throw ValidationException::withMessages([
                        'attributes' => sprintf('Attribute "%s" is placed in family variant "%s", so it must stay in the family.', $attribute->code, $variant->code),
                    ]);
                }
            }
        }
    }

    /**
     * Point the family at its label attribute, which must be one of its own
     * text attributes, or at none.
     *
     * @throws ValidationException
     */
    private function assignLabelAttribute(FamilyModel $family, ?string $code): void
    {
        if ($code === null) {
            $family->label_attribute_id = null;

            return;
        }

        $attribute = $family->familyAttributes()->where('code', $code)->first();

        if ($attribute === null) {
            throw ValidationException::withMessages([
                'label_attribute' => sprintf('Attribute "%s" is not in family "%s". Add it to the family\'s attributes first.', $code, $family->code),
            ]);
        }

        if ($attribute->type !== AttributeType::Text) {
            throw ValidationException::withMessages([
                'label_attribute' => sprintf('The label attribute must be a text attribute; "%s" is %s.', $code, $attribute->type->value),
            ]);
        }

        $family->label_attribute_id = $attribute->id;
    }
}
