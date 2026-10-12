<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Tests\Fixtures;

use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeAction;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeOptionAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeData;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeOptionData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Channel\Actions\CreateChannelAction;
use RefactorCircus\Showroom\Domains\Channel\Actions\CreateLocaleAction;
use RefactorCircus\Showroom\Domains\Channel\Data\CreateChannelData;
use RefactorCircus\Showroom\Domains\Channel\Data\CreateLocaleData;
use RefactorCircus\Showroom\Domains\Family\Actions\CreateFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Actions\CreateFamilyVariantAction;
use RefactorCircus\Showroom\Domains\Family\Data\CreateFamilyData;
use RefactorCircus\Showroom\Domains\Family\Data\CreateFamilyVariantData;

/**
 * A small apparel catalog: shirts that vary by color, then by size, sold
 * through an ecommerce channel (en, fr) and a print one (en).
 */
final class Catalog
{
    public static function apparel(): void
    {
        foreach (['en', 'fr', 'de'] as $locale) {
            app(CreateLocaleAction::class)->execute(new CreateLocaleData(code: $locale));
        }

        app(CreateChannelAction::class)->execute(new CreateChannelData(code: 'ecommerce', locales: ['en', 'fr'], currencies: ['USD', 'EUR']));
        app(CreateChannelAction::class)->execute(new CreateChannelData(code: 'print', locales: ['en'], currencies: ['USD']));

        $attribute = fn (array $data): AttributeModel => app(CreateAttributeAction::class)->execute(CreateAttributeData::from($data));
        $option = fn (AttributeModel $attribute, string $code) => app(CreateAttributeOptionAction::class)->execute($attribute, new CreateAttributeOptionData(code: $code));

        $attribute(['code' => 'name', 'type' => 'text']);
        $attribute(['code' => 'description', 'type' => 'textarea', 'is_localizable' => true]);
        $attribute(['code' => 'ean', 'type' => 'text', 'is_unique' => true]);
        $attribute(['code' => 'weight', 'type' => 'metric', 'settings' => ['metric_family' => 'weight', 'default_unit' => 'gram']]);
        $attribute(['code' => 'price', 'type' => 'price', 'settings' => ['currencies' => ['USD', 'EUR']]]);
        $attribute(['code' => 'pack_size', 'type' => 'number', 'settings' => ['min' => 1]]);
        $attribute(['code' => 'rating', 'type' => 'decimal', 'settings' => ['decimals' => 1]]);
        $attribute(['code' => 'organic', 'type' => 'boolean']);
        $attribute(['code' => 'released', 'type' => 'date']);

        $color = $attribute(['code' => 'color', 'type' => 'select']);
        $size = $attribute(['code' => 'size', 'type' => 'select']);
        $tags = $attribute(['code' => 'tags', 'type' => 'multiselect']);

        foreach (['red', 'blue', 'green'] as $code) {
            $option($color, $code);
        }

        foreach (['s', 'm', 'l'] as $code) {
            $option($size, $code);
        }

        foreach (['summer', 'sale', 'new'] as $code) {
            $option($tags, $code);
        }

        app(CreateFamilyAction::class)->execute(CreateFamilyData::from([
            'code' => 'shirts',
            'attributes' => array_map(fn (string $code): array => ['attribute' => $code], [
                'name', 'description', 'ean', 'weight', 'price', 'pack_size', 'rating', 'organic', 'released', 'color', 'size', 'tags',
            ]),
            'label_attribute' => 'name',
        ]));

        app(CreateFamilyVariantAction::class)->execute(CreateFamilyVariantData::from([
            'code' => 'shirts_by_color_size',
            'family' => 'shirts',
            'levels' => [
                ['axes' => ['color'], 'attributes' => ['price']],
                ['axes' => ['size'], 'attributes' => ['ean', 'weight']],
            ],
        ]));

        app(CreateFamilyVariantAction::class)->execute(CreateFamilyVariantData::from([
            'code' => 'shirts_by_size',
            'family' => 'shirts',
            'levels' => [
                ['axes' => ['size'], 'attributes' => ['ean']],
            ],
        ]));
    }

    /**
     * One value slot, in standard shape.
     *
     * @return array<int, array{locale: string|null, scope: string|null, data: mixed}>
     */
    public static function value(mixed $data, ?string $locale = null, ?string $scope = null): array
    {
        return [['locale' => $locale, 'scope' => $scope, 'data' => $data]];
    }
}
