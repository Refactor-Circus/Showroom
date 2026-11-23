<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Catalog\Actions\SearchCatalogAction;
use RefactorCircus\Showroom\Domains\Catalog\Data\SearchCatalogData;
use RefactorCircus\Showroom\Domains\Catalog\Data\SearchHit;
use RefactorCircus\Showroom\Domains\Catalog\Enums\SearchType;
use RefactorCircus\Showroom\Domains\Catalog\Events\CatalogSearchedActionEvent;
use RefactorCircus\Showroom\Domains\Catalog\Events\CatalogSearchingActionEvent;
use RefactorCircus\Showroom\Domains\Catalog\Resources\CatalogSearchResultResource;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use Workbench\App\Models\User;

/**
 * A catalog where every searched type has records matching "oak", and some
 * that do not, created out of result order so the order comes from the query.
 *
 * @return array<string, Model>
 */
function seedCatalogSearch(): array
{
    $asset = function (string $code, string $created, array $labels = [], ?string $filename = null): AssetModel {
        $asset = AssetModel::factory()->create(['code' => $code, 'labels' => $labels, 'filename' => $filename ?? $code.'.jpg']);
        $asset->forceFill(['created_at' => Carbon::parse($created)])->save();

        return $asset;
    };

    $vendor = OwnerTypeModel::factory()->create(['code' => 'vendor']);

    return [
        'OAK-2' => ProductModel::factory()->create(['identifier' => 'OAK-2']),
        'OAK-1' => ProductModel::factory()->create(['identifier' => 'OAK-1']),
        // Contains the term, but does not start with it.
        'BIG-OAK' => ProductModel::factory()->create(['identifier' => 'BIG-OAK']),

        'oak-photo' => $asset('oak-photo', '2026-11-01 09:00:00', ['en' => 'Oak photo']),
        // Found by its filename alone.
        'plain' => $asset('plain', '2026-11-03 09:00:00', filename: 'oak-grain.png'),
        'dark-oak' => $asset('dark-oak', '2026-11-02 09:00:00'),
        'birch' => $asset('birch', '2026-11-04 09:00:00'),

        'tables' => CategoryModel::factory()->create(['code' => 'tables', 'labels' => ['en' => 'Oak tables']]),
        'oak_furniture' => CategoryModel::factory()->create(['code' => 'oak_furniture', 'labels' => ['en' => 'Oak furniture']]),
        'chairs' => CategoryModel::factory()->create(['code' => 'chairs', 'labels' => ['en' => 'Chairs']]),

        'oakwood' => OwnerModel::factory()->create(['code' => 'oakwood', 'labels' => ['en' => 'Oakwood Ltd'], 'owner_type_id' => $vendor->id]),
        'acme' => OwnerModel::factory()->create(['code' => 'acme', 'labels' => ['en' => 'Acme'], 'owner_type_id' => $vendor->id]),

        'oak_doors' => FamilyModel::factory()->create(['code' => 'oak_doors', 'labels' => ['en' => 'Oak doors']]),
        'shirts' => FamilyModel::factory()->create(['code' => 'shirts', 'labels' => ['en' => 'Shirts']]),

        'oak_finish' => AttributeModel::factory()->create(['code' => 'oak_finish', 'labels' => ['en' => 'Oak finish'], 'type' => AttributeType::Text]),
        'weight' => AttributeModel::factory()->create(['code' => 'weight', 'labels' => ['en' => 'Weight']]),

        'oak_specs' => AttributeGroupModel::factory()->create(['code' => 'oak_specs', 'labels' => ['en' => 'Oak specs']]),
        'marketing' => AttributeGroupModel::factory()->create(['code' => 'marketing', 'labels' => ['en' => 'Marketing']]),
    ];
}

/**
 * Let every user list only the given record types.
 *
 * @param  class-string<Model>  ...$models
 */
function allowListingOnly(string ...$models): void
{
    Gate::before(fn (mixed $user, string $ability, array $arguments): ?bool => $ability === 'viewAny' && ! in_array($arguments[0] ?? null, $models, true) ? false : null);
}

/**
 * @param  Collection<int, SearchHit>  $hits
 * @return array<int, string>
 */
function hitKeys(Collection $hits): array
{
    return $hits->map(fn (SearchHit $hit): string => $hit->type->value.':'.$hit->code)->all();
}

/**
 * Search as a caller would, from untrusted input.
 *
 * @param  array<string, mixed>  $input
 * @return Collection<int, SearchHit>
 */
function searchCatalog(array $input, ?User $user = null): Collection
{
    return app(SearchCatalogAction::class)->execute(SearchCatalogData::from($input), $user);
}

it('lists the seven types in result order, each with the model it searches', function (): void {
    expect(array_column(SearchType::cases(), 'value'))->toBe(['products', 'assets', 'categories', 'owners', 'families', 'attributes', 'attribute_groups'])
        ->and(array_map(fn (SearchType $type): string => $type->model(), SearchType::cases()))->toBe([
            ProductModel::class,
            AssetModel::class,
            CategoryModel::class,
            OwnerModel::class,
            FamilyModel::class,
            AttributeModel::class,
            AttributeGroupModel::class,
        ]);
});

it('matches and orders each type as the deleted Atrium search did, in the fixed type order', function (): void {
    seedCatalogSearch();

    expect(hitKeys(searchCatalog(['q' => 'oak'])))->toBe([
        // Identifier prefix, by identifier.
        'products:OAK-1',
        'products:OAK-2',
        // Code, label or filename, newest first.
        'assets:plain',
        'assets:dark-oak',
        'assets:oak-photo',
        // Code or label, by code.
        'categories:oak_furniture',
        'categories:tables',
        'owners:oakwood',
        'families:oak_doors',
        'attributes:oak_finish',
        'attribute_groups:oak_specs',
    ]);
});

it('answers each hit as {type, id, code, label, subtitle}', function (): void {
    $records = seedCatalogSearch();

    $hits = CatalogSearchResultResource::collection(searchCatalog(['q' => 'oak']))->resolve(request());
    $byCode = collect($hits)->keyBy('code');

    expect($byCode['OAK-1'])->toBe(['type' => 'products', 'id' => $records['OAK-1']->id, 'code' => 'OAK-1', 'label' => 'OAK-1', 'subtitle' => null])
        ->and($byCode['oak-photo'])->toBe(['type' => 'assets', 'id' => $records['oak-photo']->id, 'code' => 'oak-photo', 'label' => 'Oak photo', 'subtitle' => 'oak-photo.jpg'])
        ->and($byCode['plain'])->toBe(['type' => 'assets', 'id' => $records['plain']->id, 'code' => 'plain', 'label' => 'plain', 'subtitle' => 'oak-grain.png'])
        ->and($byCode['tables'])->toBe(['type' => 'categories', 'id' => $records['tables']->id, 'code' => 'tables', 'label' => 'Oak tables', 'subtitle' => 'tables'])
        ->and($byCode['oakwood'])->toBe(['type' => 'owners', 'id' => $records['oakwood']->id, 'code' => 'oakwood', 'label' => 'Oakwood Ltd', 'subtitle' => 'oakwood · vendor'])
        ->and($byCode['oak_doors'])->toBe(['type' => 'families', 'id' => $records['oak_doors']->id, 'code' => 'oak_doors', 'label' => 'Oak doors', 'subtitle' => 'oak_doors'])
        ->and($byCode['oak_finish'])->toBe(['type' => 'attributes', 'id' => $records['oak_finish']->id, 'code' => 'oak_finish', 'label' => 'Oak finish', 'subtitle' => 'oak_finish · '.AttributeType::Text->value])
        ->and($byCode['oak_specs'])->toBe(['type' => 'attribute_groups', 'id' => $records['oak_specs']->id, 'code' => 'oak_specs', 'label' => 'Oak specs', 'subtitle' => 'oak_specs']);
});

it('searches only the one type `type` names', function (string $type, array $expected): void {
    seedCatalogSearch();

    expect(hitKeys(searchCatalog(['q' => 'oak', 'type' => $type])))->toBe($expected);
})->with([
    'products' => ['products', ['products:OAK-1', 'products:OAK-2']],
    'owners' => ['owners', ['owners:oakwood']],
    'attribute groups' => ['attribute_groups', ['attribute_groups:oak_specs']],
]);

it('caps each type at `limit`, 5 by default and at most 20', function (): void {
    foreach (range(1, 25) as $n) {
        ProductModel::factory()->create(['identifier' => sprintf('OAK-%02d', $n)]);
        CategoryModel::factory()->create(['code' => sprintf('oak_%02d', $n)]);
    }

    $default = searchCatalog(['q' => 'oak']);
    $two = searchCatalog(['q' => 'oak', 'limit' => 2]);
    $most = searchCatalog(['q' => 'oak', 'limit' => '20']);

    expect(hitKeys($default))->toBe(['products:OAK-01', 'products:OAK-02', 'products:OAK-03', 'products:OAK-04', 'products:OAK-05', 'categories:oak_01', 'categories:oak_02', 'categories:oak_03', 'categories:oak_04', 'categories:oak_05'])
        ->and(hitKeys($two))->toBe(['products:OAK-01', 'products:OAK-02', 'categories:oak_01', 'categories:oak_02'])
        ->and($most->where('type', SearchType::Products))->toHaveCount(20)
        ->and($most->where('type', SearchType::Categories))->toHaveCount(20);
});

it('takes q (at most 191 characters), an optional known type and a limit from 1 to 20', function (array $input): void {
    expect(fn () => SearchCatalogData::from($input))->toThrow(ValidationException::class);
})->with([
    'no q' => [[]],
    'an empty q' => [['q' => '']],
    'a q of 192 characters' => [['q' => str_repeat('a', 192)]],
    'an unknown type' => [['q' => 'oak', 'type' => 'transfers']],
    'a limit of 0' => [['q' => 'oak', 'limit' => 0]],
    'a limit of 21' => [['q' => 'oak', 'limit' => 21]],
    'a limit that is no number' => [['q' => 'oak', 'limit' => 'many']],
]);

it('defaults to every type and 5 per type', function (): void {
    $data = SearchCatalogData::from(['q' => str_repeat('a', 191)]);

    expect($data->type)->toBeNull()
        ->and($data->limit)->toBe(5)
        ->and($data->types())->toBe(SearchType::cases())
        ->and(SearchCatalogData::from(['q' => 'oak', 'type' => 'owners'])->types())->toBe([SearchType::Owners]);
});

it('skips the types the user may not list while authorization is on', function (): void {
    config()->set('showroom.authorization', true);
    seedCatalogSearch();
    $ann = User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']);

    allowListingOnly(ProductModel::class, OwnerModel::class);

    expect(hitKeys(searchCatalog(['q' => 'oak'], $ann)))->toBe(['products:OAK-1', 'products:OAK-2', 'owners:oakwood'])
        ->and(hitKeys(searchCatalog(['q' => 'oak', 'type' => 'assets'], $ann)))->toBe([])
        // A caller without a user may list nothing.
        ->and(hitKeys(searchCatalog(['q' => 'oak'])))->toBe([]);
});

it('searches every type while authorization is off', function (): void {
    config()->set('showroom.authorization', false);
    seedCatalogSearch();

    allowListingOnly();

    expect(searchCatalog(['q' => 'oak']))->toHaveCount(11);
});

it('fires the searching and searched events around the search', function (): void {
    Event::fake([CatalogSearchingActionEvent::class, CatalogSearchedActionEvent::class]);
    ProductModel::factory()->create(['identifier' => 'OAK-1']);

    $data = SearchCatalogData::from(['q' => 'oak']);
    $hits = app(SearchCatalogAction::class)->execute($data, null);

    Event::assertDispatched(CatalogSearchingActionEvent::class, fn (CatalogSearchingActionEvent $event): bool => $event->data === $data);
    Event::assertDispatched(CatalogSearchedActionEvent::class, fn (CatalogSearchedActionEvent $event): bool => $event->data === $data && $event->hits === $hits);
});
