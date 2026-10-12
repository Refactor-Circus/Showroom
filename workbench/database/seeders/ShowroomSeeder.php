<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use RefactorCircus\Impex\Domains\Run\Services\Engine;
use RefactorCircus\Showroom\Domains\Asset\Actions\AttachAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Actions\CreateAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Data\AttachAssetData;
use RefactorCircus\Showroom\Domains\Asset\Data\CreateAssetData;
use RefactorCircus\Showroom\Domains\Association\Actions\CreateAssociationTypeAction;
use RefactorCircus\Showroom\Domains\Association\Data\CreateAssociationTypeData;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeAction;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeGroupAction;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeOptionAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeData;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeGroupData;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeOptionData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Category\Actions\CreateCategoryAction;
use RefactorCircus\Showroom\Domains\Category\Data\CreateCategoryData;
use RefactorCircus\Showroom\Domains\Channel\Actions\CreateChannelAction;
use RefactorCircus\Showroom\Domains\Channel\Actions\CreateLocaleAction;
use RefactorCircus\Showroom\Domains\Channel\Data\CreateChannelData;
use RefactorCircus\Showroom\Domains\Channel\Data\CreateLocaleData;
use RefactorCircus\Showroom\Domains\Family\Actions\CreateFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Actions\CreateFamilyVariantAction;
use RefactorCircus\Showroom\Domains\Family\Data\CreateFamilyData;
use RefactorCircus\Showroom\Domains\Family\Data\CreateFamilyVariantData;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerTypeAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerTypeData;
use RefactorCircus\Showroom\Domains\Product\Actions\CreateProductAction;
use RefactorCircus\Showroom\Domains\Product\Actions\UpdateProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;
use RefactorCircus\Showroom\Domains\Product\Data\UpdateProductData;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Actions\CreateProductModelAction;
use RefactorCircus\Showroom\Domains\ProductModel\Data\CreateProductModelData;
use RefactorCircus\Showroom\Domains\Transfer\Actions\StartExportAction;
use RefactorCircus\Showroom\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Showroom\Domains\Transfer\Data\StartExportData;
use RefactorCircus\Showroom\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Showroom\Domains\Workflow\Actions\TransitionProductAction;
use RefactorCircus\Showroom\Domains\Workflow\Data\TransitionProductData;
use Workbench\App\Models\User;

/**
 * A small demo catalog for the workbench: apparel sold through an online
 * store (en, fr) and a print catalog (en), with owners, categories,
 * variants, associations, assets and products at every workflow stage.
 */
class ShowroomSeeder extends Seeder
{
    public function run(): void
    {
        // Versions record who made each change: Maria builds the catalog,
        // Sam reviews it.
        $this->actAs('maria@example.com');

        $this->channels();
        $this->attributes();
        $this->owners();
        $this->families();
        $this->associationTypes();
        $this->products();
        $this->moreProducts();
        $this->assets();
        $this->workflow();
        $this->edits();

        $this->actAs('test@example.com');
        $this->transfers();
    }

    private function actAs(string $email): void
    {
        Auth::guard()->setUser(User::query()->where('email', $email)->firstOrFail());
    }

    private function channels(): void
    {
        foreach (['en' => 'English', 'fr' => 'Français', 'de' => 'Deutsch'] as $code => $label) {
            app(CreateLocaleAction::class)->execute(new CreateLocaleData(code: $code, labels: ['en' => $label]));
        }

        $category = fn (string $code, ?string $parent, string $en, string $fr) => app(CreateCategoryAction::class)
            ->execute(CreateCategoryData::from(['code' => $code, 'parent' => $parent, 'labels' => ['en' => $en, 'fr' => $fr]]));

        $category('master', null, 'Master catalog', 'Catalogue principal');
        $category('apparel', 'master', 'Apparel', 'Vêtements');
        $category('tops', 'apparel', 'Tops', 'Hauts');
        $category('tshirts', 'tops', 'T-shirts', 'T-shirts');
        $category('hoodies', 'tops', 'Hoodies', 'Sweats à capuche');
        $category('accessories', 'master', 'Accessories', 'Accessoires');
        $category('hats', 'accessories', 'Hats', 'Chapeaux');
        $category('socks', 'accessories', 'Socks', 'Chaussettes');
        $category('bags', 'accessories', 'Bags', 'Sacs');
        $category('clearance', null, 'Clearance', 'Déstockage');

        app(CreateChannelAction::class)->execute(new CreateChannelData(
            code: 'ecommerce',
            labels: ['en' => 'Online store'],
            locales: ['en', 'fr'],
            currencies: ['USD', 'EUR'],
            categoryTree: 'master',
        ));

        app(CreateChannelAction::class)->execute(new CreateChannelData(
            code: 'print',
            labels: ['en' => 'Print catalog'],
            locales: ['en'],
            currencies: ['USD'],
            categoryTree: 'master',
        ));
    }

    private function attributes(): void
    {
        app(CreateAttributeGroupAction::class)->execute(CreateAttributeGroupData::from(['code' => 'marketing', 'labels' => ['en' => 'Marketing', 'fr' => 'Marketing']]));
        app(CreateAttributeGroupAction::class)->execute(CreateAttributeGroupData::from(['code' => 'technical', 'labels' => ['en' => 'Technical', 'fr' => 'Technique']]));
        app(CreateAttributeGroupAction::class)->execute(CreateAttributeGroupData::from(['code' => 'commerce', 'labels' => ['en' => 'Commerce', 'fr' => 'Commerce']]));

        $attribute = fn (array $data): AttributeModel => app(CreateAttributeAction::class)->execute(CreateAttributeData::from($data));

        $attribute(['code' => 'name', 'type' => 'text', 'group' => 'marketing', 'labels' => ['en' => 'Name', 'fr' => 'Nom']]);
        $attribute(['code' => 'description', 'type' => 'textarea', 'group' => 'marketing', 'is_localizable' => true, 'labels' => ['en' => 'Description', 'fr' => 'Description']]);
        $attribute(['code' => 'marketing_copy', 'type' => 'textarea', 'group' => 'marketing', 'is_localizable' => true, 'is_scopable' => true, 'labels' => ['en' => 'Marketing copy']]);
        $attribute(['code' => 'ean', 'type' => 'text', 'group' => 'technical', 'is_unique' => true, 'labels' => ['en' => 'EAN']]);
        $attribute(['code' => 'weight', 'type' => 'metric', 'group' => 'technical', 'settings' => ['metric_family' => 'weight', 'default_unit' => 'gram'], 'labels' => ['en' => 'Weight', 'fr' => 'Poids']]);
        $attribute(['code' => 'material', 'type' => 'text', 'group' => 'technical', 'labels' => ['en' => 'Material', 'fr' => 'Matière']]);
        $attribute(['code' => 'organic', 'type' => 'boolean', 'group' => 'technical', 'labels' => ['en' => 'Organic', 'fr' => 'Bio']]);
        $attribute(['code' => 'price', 'type' => 'price', 'group' => 'commerce', 'settings' => ['currencies' => ['USD', 'EUR']], 'labels' => ['en' => 'Price', 'fr' => 'Prix']]);
        $attribute(['code' => 'pack_size', 'type' => 'number', 'group' => 'commerce', 'settings' => ['min' => 1], 'labels' => ['en' => 'Pack size']]);
        $attribute(['code' => 'rating', 'type' => 'decimal', 'group' => 'commerce', 'settings' => ['decimals' => 1, 'min' => 0, 'max' => 5], 'labels' => ['en' => 'Rating']]);
        $attribute(['code' => 'released', 'type' => 'date', 'group' => 'commerce', 'labels' => ['en' => 'Release date']]);

        $options = [
            'color' => [['Color', 'Couleur'], [
                'red' => ['Red', 'Rouge'], 'blue' => ['Blue', 'Bleu'], 'black' => ['Black', 'Noir'],
                'heather' => ['Heather grey', 'Gris chiné'], 'natural' => ['Natural', 'Naturel'], 'olive' => ['Olive', 'Olive'],
            ]],
            'size' => [['Size', 'Taille'], ['s' => ['S', 'S'], 'm' => ['M', 'M'], 'l' => ['L', 'L'], 'xl' => ['XL', 'XL']]],
            'tags' => [['Tags', 'Étiquettes'], [
                'summer' => ['Summer', 'Été'], 'sale' => ['Sale', 'Soldes'], 'new' => ['New', 'Nouveau'],
                'bestseller' => ['Bestseller', 'Meilleure vente'], 'eco' => ['Eco', 'Écologique'],
            ]],
        ];

        foreach ($options as $code => [[$en, $fr], $choices]) {
            $select = $attribute([
                'code' => $code,
                'type' => $code === 'tags' ? 'multiselect' : 'select',
                'group' => 'marketing',
                'labels' => ['en' => $en, 'fr' => $fr],
            ]);

            foreach ($choices as $option => [$optionEn, $optionFr]) {
                app(CreateAttributeOptionAction::class)->execute($select, CreateAttributeOptionData::from(['code' => $option, 'labels' => ['en' => $optionEn, 'fr' => $optionFr]]));
            }
        }
    }

    private function owners(): void
    {
        app(CreateOwnerTypeAction::class)->execute(new CreateOwnerTypeData(code: 'manufacturer', labels: ['en' => 'Manufacturer'], parentTypes: [], canBeRoot: true, ownsProducts: false));
        app(CreateOwnerTypeAction::class)->execute(new CreateOwnerTypeData(code: 'brand', labels: ['en' => 'Brand'], parentTypes: ['manufacturer'], canBeRoot: false, ownsProducts: true));
        app(CreateOwnerTypeAction::class)->execute(new CreateOwnerTypeData(code: 'series', labels: ['en' => 'Series'], parentTypes: ['brand'], canBeRoot: false, ownsProducts: true));

        $owner = fn (string $code, string $type, ?string $parent, string $label) => app(CreateOwnerAction::class)
            ->execute(new CreateOwnerData(code: $code, type: $type, parent: $parent, labels: ['en' => $label]));

        $owner('acme', 'manufacturer', null, 'Acme Textiles');
        $owner('acme_basics', 'brand', 'acme', 'Acme Basics');
        $owner('acme_basics_classic', 'series', 'acme_basics', 'Classic series');
        $owner('acme_outdoor', 'brand', 'acme', 'Acme Outdoor');
        $owner('northwind', 'manufacturer', null, 'Northwind Mills');
        $owner('northwind_knits', 'brand', 'northwind', 'Northwind Knits');
    }

    private function families(): void
    {
        $members = fn (array $codes, array $required = [], array $onlineOnly = []): array => array_map(fn (string $code): array => [
            'attribute' => $code,
            'is_required' => in_array($code, $required, true),
            'required_channels' => in_array($code, $onlineOnly, true) ? ['ecommerce'] : null,
        ], $codes);

        app(CreateFamilyAction::class)->execute(CreateFamilyData::from([
            'code' => 'clothing',
            'labels' => ['en' => 'Clothing', 'fr' => 'Vêtements'],
            'label_attribute' => 'name',
            'attributes' => $members(
                ['name', 'description', 'marketing_copy', 'ean', 'weight', 'material', 'organic', 'price', 'rating', 'released', 'color', 'size', 'tags'],
                required: ['name', 'description', 'price', 'marketing_copy'],
                onlineOnly: ['marketing_copy'],
            ),
        ]));

        app(CreateFamilyAction::class)->execute(CreateFamilyData::from([
            'code' => 'accessories',
            'labels' => ['en' => 'Accessories', 'fr' => 'Accessoires'],
            'label_attribute' => 'name',
            'attributes' => $members(
                ['name', 'description', 'ean', 'weight', 'material', 'price', 'pack_size', 'color', 'tags'],
                required: ['name', 'description', 'price'],
            ),
        ]));

        app(CreateFamilyVariantAction::class)->execute(CreateFamilyVariantData::from([
            'code' => 'clothing_by_color_size',
            'family' => 'clothing',
            'labels' => ['en' => 'By color, then size'],
            'levels' => [
                ['axes' => ['color'], 'attributes' => ['price']],
                ['axes' => ['size'], 'attributes' => ['ean', 'weight']],
            ],
        ]));
    }

    private function associationTypes(): void
    {
        app(CreateAssociationTypeAction::class)->execute(CreateAssociationTypeData::from(['code' => 'cross_sell', 'labels' => ['en' => 'Cross-sell']]));
        app(CreateAssociationTypeAction::class)->execute(CreateAssociationTypeData::from(['code' => 'upsell', 'labels' => ['en' => 'Upsell']]));
        app(CreateAssociationTypeAction::class)->execute(CreateAssociationTypeData::from(['code' => 'goes_with', 'labels' => ['en' => 'Goes with'], 'is_two_way' => true]));
        app(CreateAssociationTypeAction::class)->execute(CreateAssociationTypeData::from(['code' => 'bundle', 'labels' => ['en' => 'Bundle contents'], 'is_quantified' => true]));
    }

    private function products(): void
    {
        $value = fn (mixed $data, ?string $locale = null, ?string $scope = null): array => [['locale' => $locale, 'scope' => $scope, 'data' => $data]];
        $price = fn (string $usd, string $eur): array => $value([['amount' => $usd, 'currency' => 'USD'], ['amount' => $eur, 'currency' => 'EUR']]);

        // A t-shirt model: common values on the root, price per color, and
        // one variant product per size.
        foreach ([
            ['tee_classic', 'Classic Tee', 'Our everyday cotton tee.', 'Le t-shirt en coton de tous les jours.', 'acme_basics_classic', ['red' => ['19.00', '18.00'], 'blue' => ['19.00', '18.00'], 'black' => ['21.00', '20.00']]],
            ['hoodie_trail', 'Trail Hoodie', 'A warm fleece hoodie for cold mornings.', 'Un sweat polaire chaud pour les matins froids.', 'acme_outdoor', ['heather' => ['59.00', '55.00'], 'black' => ['59.00', '55.00']]],
        ] as $index => [$code, $name, $en, $fr, $owner, $colors]) {
            app(CreateProductModelAction::class)->execute(CreateProductModelData::from([
                'code' => $code,
                'family_variant' => 'clothing_by_color_size',
                'owner' => $owner,
                'categories' => [$code === 'tee_classic' ? 'tshirts' : 'hoodies'],
                'values' => [
                    'name' => $value($name),
                    'description' => [
                        ['locale' => 'en', 'scope' => null, 'data' => $en],
                        ['locale' => 'fr', 'scope' => null, 'data' => $fr],
                    ],
                    'marketing_copy' => $value($name.' — made to last.', 'en', 'ecommerce'),
                    'material' => $value($code === 'tee_classic' ? '100% organic cotton' : '80% cotton, 20% polyester'),
                    'organic' => $value($code === 'tee_classic'),
                    'rating' => $value($code === 'tee_classic' ? '4.6' : '4.2'),
                    'released' => $value($code === 'tee_classic' ? '2025-03-01' : '2026-09-01'),
                    'tags' => $value($code === 'tee_classic' ? ['bestseller', 'summer'] : ['new']),
                ],
            ]));

            foreach ($colors as $color => [$usd, $eur]) {
                app(CreateProductModelAction::class)->execute(CreateProductModelData::from([
                    'code' => $code.'_'.$color,
                    'parent' => $code,
                    'values' => ['color' => $value($color), 'price' => $price($usd, $eur)],
                ]));

                foreach (['s', 'm', 'l', 'xl'] as $sizeIndex => $size) {
                    app(CreateProductAction::class)->execute(CreateProductData::from([
                        'identifier' => strtoupper(str_replace('_', '-', $code).'-'.$color.'-'.$size),
                        'parent' => $code.'_'.$color,
                        'values' => [
                            'size' => $value($size),
                            'ean' => $value(sprintf('400%d%03d%02d%04d', $index, crc32($color) % 1000, $sizeIndex, crc32($code) % 10000)),
                            'weight' => $value(['amount' => ($code === 'tee_classic' ? 160 : 520) + $sizeIndex * 20, 'unit' => 'gram']),
                        ],
                    ]));
                }
            }
        }

        // Simple products.
        $simple = fn (string $identifier, string $family, string $owner, array $categories, array $values, array $extra = []) => app(CreateProductAction::class)->execute(CreateProductData::from([
            'identifier' => $identifier,
            'family' => $family,
            'owner' => $owner,
            'categories' => $categories,
            'values' => $values,
        ] + $extra));

        $simple('CAP-BASEBALL', 'accessories', 'acme_basics', ['hats'], [
            'name' => $value('Baseball Cap'),
            'description' => [['locale' => 'en', 'scope' => null, 'data' => 'Six-panel cap with an adjustable strap.'], ['locale' => 'fr', 'scope' => null, 'data' => 'Casquette six panneaux à sangle réglable.']],
            'price' => $price('15.00', '14.00'),
            'color' => $value('black'),
            'material' => $value('Cotton twill'),
            'ean' => $value('4009000000011'),
        ]);

        $simple('BEANIE-KNIT', 'accessories', 'northwind_knits', ['hats'], [
            'name' => $value('Knit Beanie'),
            'description' => [['locale' => 'en', 'scope' => null, 'data' => 'Chunky rib-knit beanie.']],
            'price' => $price('22.00', '20.00'),
            'color' => $value('heather'),
            'tags' => $value(['new']),
        ]);

        $simple('SOCKS-CREW', 'accessories', 'northwind_knits', ['socks'], [
            'name' => $value('Crew Socks'),
            'price' => $price('9.00', '8.50'),
            'pack_size' => $value(1),
        ]);

        // A bundle: three pairs of socks and a beanie; the cap goes with it.
        $simple('WINTER-BUNDLE', 'accessories', 'northwind_knits', ['accessories'], [
            'name' => $value('Winter Bundle'),
            'description' => [['locale' => 'en', 'scope' => null, 'data' => 'Three pairs of crew socks and a knit beanie.']],
            'price' => $price('39.00', '36.00'),
            'tags' => $value(['sale']),
        ], [
            'quantified_associations' => ['bundle' => ['products' => [
                ['identifier' => 'SOCKS-CREW', 'quantity' => 3],
                ['identifier' => 'BEANIE-KNIT', 'quantity' => 1],
            ]]],
            'associations' => ['goes_with' => ['products' => ['CAP-BASEBALL']]],
        ]);

        // An incomplete draft with no family requirements met.
        $simple('SCARF-DRAFT', 'accessories', 'northwind_knits', [], ['name' => $value('Scarf (draft)')]);

        app(UpdateProductAction::class)->execute(ProductModel::query()->where('identifier', 'CAP-BASEBALL')->firstOrFail(), UpdateProductData::from([
            'associations' => [
                'cross_sell' => ['product_models' => ['tee_classic']],
                'upsell' => ['products' => ['BEANIE-KNIT']],
            ],
        ]));
    }

    private function moreProducts(): void
    {
        $value = fn (mixed $data, ?string $locale = null, ?string $scope = null): array => [['locale' => $locale, 'scope' => $scope, 'data' => $data]];
        $text = fn (string $en, ?string $fr = null): array => array_values(array_filter([
            ['locale' => 'en', 'scope' => null, 'data' => $en],
            $fr === null ? null : ['locale' => 'fr', 'scope' => null, 'data' => $fr],
        ]));
        $price = fn (string $usd, string $eur): array => $value([['amount' => $usd, 'currency' => 'USD'], ['amount' => $eur, 'currency' => 'EUR']]);

        $simple = fn (string $identifier, string $owner, array $categories, array $values, array $extra = []) => app(CreateProductAction::class)->execute(CreateProductData::from([
            'identifier' => $identifier,
            'family' => 'accessories',
            'owner' => $owner,
            'categories' => $categories,
            'values' => $values,
        ] + $extra));

        $simple('TOTE-CANVAS', 'acme_basics', ['bags'], [
            'name' => $value('Canvas Tote'),
            'description' => $text('Heavy cotton canvas tote with long handles.', 'Tote bag en toile de coton épaisse à longues anses.'),
            'price' => $price('24.00', '22.00'),
            'color' => $value('natural'),
            'material' => $value('12 oz cotton canvas'),
            'weight' => $value(['amount' => 340, 'unit' => 'gram']),
            'ean' => $value('4009000000028'),
            'tags' => $value(['eco', 'bestseller']),
        ]);

        $simple('BACKPACK-DAY', 'acme_outdoor', ['bags'], [
            'name' => $value('Day Backpack 20L'),
            'description' => $text('A light 20-litre pack with a padded laptop sleeve.', 'Sac à dos léger de 20 litres avec housse pour ordinateur.'),
            'price' => $price('79.00', '74.00'),
            'color' => $value('olive'),
            'material' => $value('Recycled ripstop nylon'),
            'weight' => $value(['amount' => 0.62, 'unit' => 'kilogram']),
            'ean' => $value('4009000000035'),
            'tags' => $value(['new', 'eco']),
        ], ['associations' => ['goes_with' => ['products' => ['TOTE-CANVAS']]]]);

        $simple('SOCKS-WOOL', 'northwind_knits', ['socks'], [
            'name' => $value('Merino Hiking Socks'),
            'description' => $text('Cushioned merino socks for long days on the trail.', 'Chaussettes en mérinos rembourrées pour la randonnée.'),
            'price' => $price('18.00', '17.00'),
            'pack_size' => $value(2),
            'material' => $value('70% merino wool'),
            'color' => $value('heather'),
        ], ['associations' => ['upsell' => ['products' => ['SOCKS-CREW']]]]);

        $simple('GLOVES-FLEECE', 'acme_outdoor', ['accessories'], [
            'name' => $value('Fleece Gloves'),
            'description' => $text('Touchscreen-friendly fleece gloves.'),
            'price' => $price('16.00', '15.00'),
            'color' => $value('black'),
        ]);

        // Retired: archived, and filed under the clearance tree.
        $simple('CAP-TRUCKER', 'acme_basics', ['hats', 'clearance'], [
            'name' => $value('Trucker Cap'),
            'description' => $text('Mesh-back trucker cap. Discontinued.'),
            'price' => $price('12.00', '11.00'),
            'color' => $value('red'),
            'tags' => $value(['sale']),
        ], ['enabled' => false]);
    }

    private function assets(): void
    {
        $images = [
            'tee_classic' => ['product_model', 'main_image', [220, 60, 60]],
            'tee_classic_blue' => ['product_model', 'main_image', [50, 90, 200]],
            'hoodie_trail' => ['product_model', 'main_image', [120, 120, 130]],
            'CAP-BASEBALL' => ['product', 'main_image', [30, 30, 30]],
            'BEANIE-KNIT' => ['product', 'main_image', [150, 150, 160]],
            'TOTE-CANVAS' => ['product', 'main_image', [225, 210, 180]],
            'BACKPACK-DAY' => ['product', 'main_image', [100, 115, 70]],
            'SOCKS-WOOL' => ['product', 'main_image', [140, 140, 150]],
            'acme' => ['owner', 'logo', [40, 90, 200]],
            'northwind' => ['owner', 'logo', [20, 120, 110]],
        ];

        foreach ($images as $target => [$type, $role, [$red, $green, $blue]]) {
            $file = UploadedFile::fake()->image(strtolower(str_replace('_', '-', $target)).'.png', 600, 600);

            // Tint the placeholder so each asset is recognisable.
            $image = imagecreatetruecolor(600, 600);
            imagefill($image, 0, 0, (int) imagecolorallocate($image, $red, $green, $blue));
            imagepng($image, $file->getPathname());

            $asset = app(CreateAssetAction::class)->execute(CreateAssetData::from([
                'code' => strtolower(str_replace('_', '-', $target)).'-'.($role === 'logo' ? 'logo' : 'main'),
                'labels' => ['en' => ucwords(str_replace(['_', '-'], ' ', strtolower($target)))],
                'file' => $file,
            ]));

            app(AttachAssetAction::class)->execute($asset, AttachAssetData::from(['type' => $type, 'target' => $target, 'role' => $role]));
        }
    }

    private function workflow(): void
    {
        $transition = function (string $identifier, array $transitions): void {
            foreach ($transitions as $name => $comment) {
                [$name, $comment] = is_int($name) ? [$comment, null] : [$name, $comment];

                // Editors submit; reviewers decide and publish.
                $this->actAs($name === 'submit' ? 'maria@example.com' : 'sam@example.com');

                $product = ProductModel::query()->where('identifier', $identifier)->firstOrFail();

                app(TransitionProductAction::class)->execute($product, TransitionProductData::from(['transition' => $name, 'comment' => $comment]));
            }

            $this->actAs('maria@example.com');
        };

        $live = ['submit' => 'Ready for review.', 'approve' => 'Looks good.', 'publish' => 'Live on all channels.'];

        foreach (['CAP-BASEBALL', 'TOTE-CANVAS', 'SOCKS-CREW'] as $identifier) {
            $transition($identifier, $live);
        }

        foreach (['red', 'blue'] as $color) {
            foreach (['s', 'm', 'l', 'xl'] as $size) {
                $transition(strtoupper('tee-classic-'.$color.'-'.$size), $live);
            }
        }

        $transition('BEANIE-KNIT', ['submit', 'approve' => 'Approved; publish with the winter campaign.']);
        $transition('HOODIE-TRAIL-HEATHER-M', ['submit', 'approve']);
        $transition('BACKPACK-DAY', ['submit' => 'New for spring, please check the French copy.']);
        $transition('WINTER-BUNDLE', ['submit']);
        $transition('TEE-CLASSIC-BLACK-M', ['submit']);
        $transition('GLOVES-FLEECE', ['submit', 'reject' => 'Needs a French description and an EAN.']);
        $transition('CAP-TRUCKER', ['submit', 'approve', 'publish', 'unpublish' => 'Discontinued.', 'archive' => 'Discontinued; kept for order history.']);
    }

    /**
     * Changes after publishing, so a live product has a newer working copy
     * and a version history to compare.
     */
    private function edits(): void
    {
        $product = ProductModel::query()->where('identifier', 'CAP-BASEBALL')->firstOrFail();

        app(UpdateProductAction::class)->execute($product, UpdateProductData::from([
            'values' => [
                'description' => [
                    ['locale' => 'en', 'scope' => null, 'data' => 'Six-panel cotton twill cap with an adjustable brass buckle.'],
                    ['locale' => 'fr', 'scope' => null, 'data' => 'Casquette six panneaux en sergé de coton, boucle en laiton réglable.'],
                ],
                'tags' => [['locale' => null, 'scope' => null, 'data' => ['bestseller']]],
            ],
        ]));

        $product = ProductModel::query()->where('identifier', 'SOCKS-CREW')->firstOrFail();

        app(UpdateProductAction::class)->execute($product, UpdateProductData::from([
            'values' => ['pack_size' => [['locale' => null, 'scope' => null, 'data' => 3]]],
        ]));
    }

    /**
     * Impex runs in every state. With QUEUE_CONNECTION=sync, runs finish as
     * they start; the pending and running ones are queued on a connection that
     * drops their jobs, so they stay where they are.
     */
    private function transfers(): void
    {
        app(StartExportAction::class)->execute(StartExportData::from(['family' => 'clothing', 'format' => 'csv', 'code' => 'export-clothing']));
        app(StartExportAction::class)->execute(StartExportData::from(['scope' => 'ecommerce', 'locales' => ['en', 'fr'], 'published' => true, 'code' => 'export-ecommerce-live']));

        app(StartImportAction::class)->execute(StartImportData::from(['mode' => 'upsert', 'file' => $this->csv('spring-socks.csv', [
            ['identifier', 'family', 'owner', 'categories', 'name', 'description-en', 'price-USD', 'price-EUR', 'pack_size', 'color', 'tags'],
            ['SOCKS-ANKLE', 'accessories', 'northwind_knits', 'socks', 'Ankle Socks', 'Low-cut cotton socks.', '7.00', '6.50', '3', 'black', 'summer,new'],
            ['SOCKS-STRIPE', 'accessories', 'northwind_knits', 'socks', 'Striped Socks', 'Bold striped crew socks.', '10.00', '9.50', '1', 'blue', 'new'],
            ['SOCKS-CREW', '', '', '', '', '', '9.50', '9.00', '', '', ''],
        ])]));

        // A supplier file with a bad row fails outright when no failures are tolerated.
        $tolerance = config('showroom.impex.allow_failures');
        config(['showroom.impex.allow_failures' => 0.0]);

        app(StartImportAction::class)->execute(StartImportData::from(['mode' => 'create', 'file' => $this->csv('supplier-feed.csv', [
            ['identifier', 'family', 'name', 'price-USD'],
            ['SCARF-WOOL', 'accessories', 'Wool Scarf', '29.00'],
            ['SCARF-SILK', 'scarves', 'Silk Scarf', '49.00'],
        ])]));

        config(['showroom.impex.allow_failures' => $tolerance]);

        $connection = config('impex.queue.connection');
        config(['queue.connections.workbench-held' => ['driver' => 'null'], 'impex.queue.connection' => 'workbench-held']);

        app(StartExportAction::class)->execute(StartExportData::from(['family' => 'accessories', 'format' => 'jsonl', 'code' => 'export-accessories']));

        $running = app(StartImportAction::class)->execute(StartImportData::from(['mode' => 'upsert', 'file' => $this->csv('price-update.csv', [
            ['identifier', 'price-USD', 'price-EUR'],
            ['BEANIE-KNIT', '24.00', '22.00'],
        ])]));

        // Drive it once: it starts, and its first step is queued (and dropped).
        app(Engine::class)->drive((string) $running->getKey());

        config(['impex.queue.connection' => $connection]);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function csv(string $name, array $rows): UploadedFile
    {
        $lines = array_map(
            fn (array $row): string => implode(',', array_map(fn (string $cell): string => str_contains($cell, ',') ? '"'.$cell.'"' : $cell, $row)),
            $rows,
        );

        return UploadedFile::fake()->createWithContent($name, implode("\n", $lines)."\n");
    }
}
