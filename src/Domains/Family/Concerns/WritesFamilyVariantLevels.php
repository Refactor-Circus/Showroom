<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Concerns;

use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Family\Data\FamilyLevelData;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;

/**
 * The rules for a family variant's levels, shared by create and update.
 */
trait WritesFamilyVariantLevels
{
    /**
     * Place attributes on the variant's levels, replacing any placement.
     *
     * @param  array<int, FamilyLevelData>  $levels
     *
     * @throws ValidationException
     */
    private function placeLevels(FamilyVariantModel $variant, array $levels): void
    {
        $family = $variant->family()->with('familyAttributes')->firstOrFail();
        $members = $family->familyAttributes->keyBy('code');

        $errors = [];
        $placed = [];
        $sync = [];

        foreach ($levels as $index => $level) {
            $number = $index + 1;

            foreach (['axes' => $level->axes, 'attributes' => $level->attributes] as $kind => $codes) {
                foreach ($codes as $position => $code) {
                    $key = sprintf('levels.%d.%s.%d', $index, $kind, $position);
                    $attribute = $members->get($code);

                    if (! $attribute instanceof AttributeModel) {
                        $errors[$key] = sprintf('Attribute "%s" is not in family "%s".', $code, $family->code);

                        continue;
                    }

                    if (isset($placed[$code])) {
                        $errors[$key] = sprintf('Attribute "%s" is placed more than once.', $code);

                        continue;
                    }

                    if ($kind === 'axes' && ! $this->canBeAxis($attribute)) {
                        $errors[$key] = sprintf('Attribute "%s" cannot be an axis: axes are select, boolean or metric attributes that are neither localizable nor scopable.', $code);

                        continue;
                    }

                    $placed[$code] = $number;
                    $sync[$attribute->id] = ['level' => $number, 'is_axis' => $kind === 'axes'];
                }
            }
        }

        // A unique value belongs to one product, so it can only be set on
        // the last level, where each product has its own.
        foreach ($members as $code => $attribute) {
            if ($attribute->is_unique && ($placed[$code] ?? 0) !== count($levels)) {
                $errors['levels'] = sprintf('Unique attribute "%s" must be placed on the last level.', $code);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $variant->levels = count($levels);
        $variant->save();
        $variant->variantAttributes()->sync($sync);
    }

    private function canBeAxis(AttributeModel $attribute): bool
    {
        return in_array($attribute->type, [AttributeType::Select, AttributeType::Boolean, AttributeType::Metric], true)
            && ! $attribute->is_localizable
            && ! $attribute->is_scopable;
    }
}
