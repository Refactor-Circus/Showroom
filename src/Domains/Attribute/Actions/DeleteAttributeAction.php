<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Exceptions\AttributeLabelsFamiliesException;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;
use RefactorCircus\Showroom\Exceptions\ModelInUseException;
use RefactorCircus\Showroom\Jobs\PurgeAttributeValues;

final class DeleteAttributeAction
{
    public function execute(AttributeModel $attribute): AttributeModel
    {
        AttributeDeletingActionEvent::dispatch($attribute);

        $result = $this->perform($attribute);

        AttributeDeletedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * Options and family memberships go with the attribute; the foreign keys
     * cascade, and stored values are purged by a queued job. An attribute that
     * labels a family or shapes a family variant is refused instead.
     */
    private function perform(AttributeModel $attribute): AttributeModel
    {
        return DB::transaction(function () use ($attribute): AttributeModel {
            /** @var array<int, string> $families */
            $families = FamilyModel::query()->where('label_attribute_id', $attribute->id)->orderBy('code')->pluck('code')->all();

            if ($families !== []) {
                throw AttributeLabelsFamiliesException::for($attribute, $families);
            }

            /** @var array<int, string> $variants */
            $variants = FamilyVariantModel::query()
                ->whereHas('variantAttributes', fn (Builder $attributes): Builder => $attributes->whereKey($attribute->id))
                ->orderBy('code')
                ->pluck('code')
                ->all();

            if ($variants !== []) {
                throw ModelInUseException::attributeInFamilyVariants($attribute->code, $variants);
            }

            $attribute->delete();

            // Stored values outlive the attribute until the purge reaches them.
            PurgeAttributeValues::dispatch($attribute->code)->afterCommit();

            return $attribute;
        });
    }
}
