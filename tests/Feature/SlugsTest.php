<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use RefactorCircus\Showroom\Domains\Attribute\Data\ValueFilter;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\UpdateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\CreateOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\ListOwnersTool;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Actions\CreateProductAction;
use RefactorCircus\Showroom\Domains\Product\Actions\UpdateProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;
use RefactorCircus\Showroom\Domains\Product\Data\UpdateProductData;
use RefactorCircus\Showroom\Domains\Product\Events\ProductCreatingEvent;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\CreateProductTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ListProductsTool;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Search\Services\ProductDocument;
use RefactorCircus\Showroom\Impex\Actions\ExportProductPages;
use RefactorCircus\Showroom\Impex\ExportRecord;
use RefactorCircus\Showroom\Impex\ProductRows;
use RefactorCircus\Showroom\Support\Slugs;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

function slugProduct(string $identifier, ?string $owner = null): ProductModel
{
    return app(CreateProductAction::class)->execute(new CreateProductData(identifier: $identifier, owner: $owner));
}

/**
 * @param  array<string, string>|null  $labels
 */
function slugOwner(string $code, ?array $labels = null, ?string $parent = null): OwnerModel
{
    if (! OwnerTypeModel::query()->where('code', 'vendor')->exists()) {
        OwnerTypeModel::factory()->create(['code' => 'vendor']);
    }

    return app(CreateOwnerAction::class)->execute(new CreateOwnerData(code: $code, type: 'vendor', parent: $parent, labels: $labels));
}

describe('the generator', function (): void {
    it('suffixes a slug another product holds', function (): void {
        expect(slugProduct('A/B')->slug)->toBe('a-b')
            ->and(slugProduct('A B')->slug)->toBe('a-b-2');
    });

    it('gives same-base sources in one call the next suffixes, with no gaps', function (): void {
        ProductModel::factory()->create(['identifier' => 'A', 'slug' => 'a']);

        expect(app(Slugs::class)->forMany('products', ['first' => 'A', 'second' => 'a']))->toBe(['first' => 'a-2', 'second' => 'a-3']);
    });

    it('counts existing slugs and slugs already given in the call as taken', function (): void {
        $slugs = app(Slugs::class)->forMany('products', ['a', 'a', 'a-2']);

        expect($slugs)->toHaveCount(3)
            ->and(array_unique($slugs))->toHaveCount(3)
            ->and($slugs[0])->toBe('a')
            ->and($slugs[1])->toBe('a-2');
    });

    it('cuts a collided 191-character source to fit with its suffix', function (): void {
        $identifier = str_repeat('a', 191);

        [$first, $second] = app(Slugs::class)->forMany('products', [$identifier, $identifier]);

        expect($first)->toBe($identifier)
            ->and($second)->toHaveLength(191)->toEndWith('-2');
    });

    it('falls back to product for a source that slugifies to nothing', function (): void {
        expect(slugProduct('///')->slug)->toBe('product')
            ->and(slugProduct('!!!')->slug)->toBe('product-2');
    });

    it('suffixes a reserved route segment', function (): void {
        expect(slugProduct('versions')->slug)->toBe('versions-2')
            ->and(app(Slugs::class)->for('owners', 'Transitions'))->toBe('transitions-2');
    });

    it('reserves every literal route segment beside or after a product or owner key', function (): void {
        $routes = collect(app('router')->getRoutes()->getRoutes());
        $segments = [];

        foreach ($routes as $route) {
            $parts = explode('/', $route->uri());

            foreach ($parts as $position => $part) {
                if (preg_match('/^\{(product|owner)(:[^}]+)?\}$/', $part) !== 1) {
                    continue;
                }

                // Literal segments after the key: products/{product}/versions.
                foreach (array_slice($parts, $position + 1) as $after) {
                    if (! str_starts_with($after, '{')) {
                        $segments[] = $after;
                    }
                }

                // Literal segments in the key's own place: products/create.
                $prefix = array_slice($parts, 0, $position);

                foreach ($routes as $sibling) {
                    $siblingParts = explode('/', $sibling->uri());
                    $candidate = $siblingParts[$position] ?? '{';

                    if (array_slice($siblingParts, 0, $position) === $prefix && ! str_starts_with($candidate, '{')) {
                        $segments[] = $candidate;
                    }
                }
            }
        }

        $segments = array_values(array_unique($segments));
        sort($segments);

        expect($segments)->toContain('versions', 'revert', 'transitions')
            ->and(array_values(array_diff($segments, Slugs::RESERVED)))->toBe([]);
    });

    it('retries once with a fresh lookup when a concurrent write takes the slug first', function (): void {
        $attempts = 0;

        Event::listen(ProductCreatingEvent::class, function (ProductCreatingEvent $event) use (&$attempts): void {
            if ($event->product->identifier === 'TEE' && $attempts++ === 0) {
                // Another request claims the same slug between the lookup and the insert.
                ProductModel::factory()->create(['identifier' => 'RACER', 'slug' => $event->product->slug]);
            }
        });

        $product = slugProduct('TEE');

        expect($attempts)->toBe(2)
            ->and($product->exists)->toBeTrue()
            ->and(ProductModel::query()->where('identifier', 'TEE')->value('slug'))->toBe($product->slug);
    });
});

describe('owners', function (): void {
    it('slugs the default-locale label, then the code, then owner', function (): void {
        expect(slugOwner('acme', ['en' => 'Acme Corp', 'fr' => 'Acmé SA'])->slug)->toBe('acme-corp')
            ->and(slugOwner('globex')->slug)->toBe('globex')
            ->and(slugOwner('initech', ['fr' => 'Initech France'])->slug)->toBe('initech')
            ->and(slugOwner('hooli', ['en' => '!!!'])->slug)->toBe('hooli')
            ->and(OwnerModel::factory()->create(['code' => '///', 'labels' => []])->slug)->toBe('owner');
    });

    it('keeps slugs unique across all owners, whatever their parent', function (): void {
        slugOwner('acme', ['en' => 'Classic']);
        slugOwner('globex');

        expect(slugOwner('acme-classic', ['en' => 'Classic'], 'acme')->slug)->toBe('classic-2')
            ->and(slugOwner('globex-classic', ['en' => 'Classic'], 'globex')->slug)->toBe('classic-3');
    });

    it('keeps its slug when its labels or parent change', function (): void {
        slugOwner('acme');
        $owner = slugOwner('classic', ['en' => 'Classic']);

        $owner = app(UpdateOwnerAction::class)->execute($owner, UpdateOwnerData::from(['labels' => ['en' => 'Renamed'], 'parent' => 'acme']));

        expect($owner->refresh()->slug)->toBe('classic')
            ->and($owner->parent_id)->not->toBeNull();
    });
});

describe('products', function (): void {
    it('slugs the identifier on creation', function (): void {
        expect(slugProduct('83AL-IC/40 4 RED ALUM')->slug)->toBe('83al-ic-40-4-red-alum')
            ->and(ProductModel::factory()->create(['identifier' => 'ML20906 HPSK 630'])->slug)->toBe('ml20906-hpsk-630');
    });

    it('keeps its slug when its values change', function (): void {
        Catalog::apparel();
        $product = slugProduct('TEE');

        app(UpdateProductAction::class)->execute($product, UpdateProductData::from([
            'enabled' => false,
            'values' => ['name' => Catalog::value('Renamed tee')],
        ]));

        expect($product->refresh()->slug)->toBe('tee')
            ->and($product->enabled)->toBeFalse();
    });
});

describe('read-only on input', function (): void {
    it('refuses a slug sent on product create', function (): void {
        $this->postJson(route('showroom.products.store'), ['identifier' => 'TEE', 'slug' => 'my-tee'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);

        expect(ProductModel::query()->count())->toBe(0);
    });

    it('refuses a full product resource sent back on update, naming the read-only keys', function (): void {
        $body = $this->postJson(route('showroom.products.store'), ['identifier' => 'TEE'])->assertCreated()->json('data');
        $product = ProductModel::query()->where('identifier', 'TEE')->firstOrFail();

        $this->patchJson(route('showroom.products.update', $product), [...$body, 'slug' => 'renamed'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug', 'identifier']);

        expect($product->refresh()->slug)->toBe('tee');
    });

    it('refuses a slug sent on owner create', function (): void {
        OwnerTypeModel::factory()->create(['code' => 'vendor']);

        $this->postJson(route('showroom.owners.store'), ['code' => 'acme', 'type' => 'vendor', 'slug' => 'acme'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });

    it('refuses a full owner resource sent back on update, naming the read-only keys', function (): void {
        OwnerTypeModel::factory()->create(['code' => 'vendor']);
        $body = $this->postJson(route('showroom.owners.store'), ['code' => 'acme', 'type' => 'vendor'])->assertCreated()->json('data');
        $owner = OwnerModel::query()->where('code', 'acme')->firstOrFail();

        $this->patchJson(route('showroom.owners.update', $owner), $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug', 'code', 'type']);

        expect($owner->refresh()->slug)->toBe('acme');
    });
});

describe('output', function (): void {
    it('returns the slug in product and owner resources', function (): void {
        OwnerTypeModel::factory()->create(['code' => 'vendor']);

        $this->postJson(route('showroom.owners.store'), ['code' => 'acme', 'type' => 'vendor', 'labels' => ['en' => 'Acme Corp']])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'acme-corp');
        $this->postJson(route('showroom.products.store'), ['identifier' => 'TEE/1', 'owner' => 'acme'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'tee-1');

        $this->getJson(route('showroom.owners.index'))->assertJsonPath('data.0.slug', 'acme-corp');
        $this->getJson(route('showroom.products.index'))->assertJsonPath('data.0.slug', 'tee-1');
    });

    it('returns the slug from the MCP tools', function (): void {
        OwnerTypeModel::factory()->create(['code' => 'vendor']);

        mcpTool(CreateOwnerTool::class, ['code' => 'acme', 'type' => 'vendor', 'labels' => ['en' => 'Acme Corp']])->assertOk()->assertSee('"slug":"acme-corp"');
        mcpTool(ListOwnersTool::class)->assertOk()->assertSee('"slug":"acme-corp"');
        mcpTool(CreateProductTool::class, ['identifier' => 'TEE/1'])->assertOk()->assertSee('"slug":"tee-1"');
        mcpTool(ListProductsTool::class)->assertOk()->assertSee('"slug":"tee-1"');
    });

    it('puts the slug and the owner chain\'s slugs in the search document', function (): void {
        slugOwner('acme', ['en' => 'Acme Corp']);
        slugOwner('classic', ['en' => 'Classic Line'], 'acme');
        $product = slugProduct('TEE/1', 'classic');

        $document = ProductDocument::from($product->fresh());

        expect($document['slug'])->toBe('tee-1')
            ->and($document['owners'])->toBe(['acme', 'classic'])
            ->and($document['owner_slugs'])->toBe(['acme-corp', 'classic-line']);
    });

    it('exports the slug, and reads past it on import', function (): void {
        $product = slugProduct('TEE/1');
        $product = ProductModel::query()->with(ExportProductPages::eagerLoads())->findOrFail($product->id);

        $record = ExportRecord::of($product, new ValueFilter);
        $row = app(ProductRows::class)->toCsv($record);

        expect($record['slug'])->toBe('tee-1')
            ->and(array_slice(array_keys($row), 0, 2))->toBe(['identifier', 'slug'])
            ->and($row['slug'])->toBe('tee-1')
            ->and(app(ProductRows::class)->fromCsv(['identifier' => 'TEE/1', 'slug' => 'tee-1']))->toBe(['identifier' => 'TEE/1']);
    });
});
